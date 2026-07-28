<?php

namespace App\Http\Middleware;

use App\Services\ErpIntegrationService;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\Response;

class AuditModuleAction
{
    public function handle(Request $request, Closure $next): Response
    {
        // BI records chat conversations in its own client-scoped audit table.
        // Do not make the interactive response wait for a second write to the
        // ITSM database after the AI provider has already answered.
        if ($request->routeIs('bi.ai.chat')) {
            return $next($request);
        }

        $clientId = $this->clientId($request);
        $department = $this->department($request);
        $actor = session('employee_name') ?: $request->user()?->username ?: $request->user()?->email;

        try {
            $response = $next($request);
        } catch (\Throwable $exception) {
            $this->recordFailure($request, $clientId, $department, $actor, $this->exceptionStatus($exception), $exception);

            throw $exception;
        }

        if ($response->getStatusCode() >= 400) {
            $this->recordFailure($request, $clientId, $department, $actor, $response->getStatusCode());

            return $response;
        }

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $this->record($clientId, 'action.'.strtolower($request->method()), $department, $this->requestDetails($request, $actor, $response->getStatusCode()));
        }

        return $response;
    }

    private function recordFailure(Request $request, ?int $clientId, string $department, ?string $actor, int $status, ?\Throwable $exception = null): void
    {
        if (! in_array($status, [403, 404, 500], true) && $status < 500) {
            return;
        }

        $details = $this->requestDetails($request, $actor, $status);
        $details['error'] = [
            'status' => $status,
            'type' => $exception ? class_basename($exception) : null,
            // Never put an exception message or stack trace in a user-visible
            // audit record; database queries and credentials may be present.
            'message' => match ($status) {
                403 => 'Access was denied.',
                404 => 'The requested resource was not found.',
                default => 'The request could not be completed by the server.',
            },
        ];

        $this->record($clientId, 'request.error', $department, $details);
    }

    private function record(?int $clientId, string $event, string $department, array $details): void
    {
        if ($clientId === null) {
            return;
        }

        try {
            app(ErpIntegrationService::class)->recordAudit($clientId, $event, $department, $details);
        } catch (\Throwable $exception) {
            Log::warning('ERP audit logging failed after a completed request.', [
                'route' => request()->route()?->getName(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function clientId(Request $request): ?int
    {
        $clientId = (int) (session('employee_client_id') ?: $request->attributes->get('ecommerce_company')?->id ?: $request->user()?->company_id);

        if ($clientId > 0) {
            return $clientId;
        }

        // Root administration has no client id. Store its audit records under
        // zero so they are visible only from the root audit trail.
        return $request->user()?->role === 'root_admin' ? 0 : null;
    }

    private function requestDetails(Request $request, ?string $actor, int $status): array
    {
        return [
            'actor' => $actor,
            'request' => [
                'method' => $request->method(),
                'route' => $request->route()?->getName(),
                'path' => '/'.$request->path(),
                'query' => $this->redact($request->query()),
                'input' => $this->redact($request->input()),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 300),
            ],
            'response' => ['status' => $status],
        ];
    }

    private function redact(array $values): array
    {
        $sensitive = ['password', 'password_confirmation', 'current_password', 'temporary_password', 'token', '_token', 'api_key', 'secret', 'client_secret'];

        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitive, true) || str_contains(strtolower((string) $key), 'password')) {
                $values[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $values[$key] = $this->redact($value);
            } elseif (is_string($value)) {
                $values[$key] = Str::limit($value, 1000);
            }
        }

        return $values;
    }

    private function exceptionStatus(\Throwable $exception): int
    {
        if ($exception instanceof ModelNotFoundException) {
            return 404;
        }

        if ($exception instanceof AuthorizationException) {
            return 403;
        }

        return $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
    }

    private function department(Request $request): string
    {
        $route = (string) $request->route()?->getName();
        foreach (['hr' => 'Human Resources', 'inventory' => 'Inventory', 'procurement' => 'Procurement', 'manufacturing' => 'Manufacturing', 'finance' => 'Finance', 'order-fulfillment' => 'Order Fulfillment', 'ecommerce' => 'E-commerce', 'bi' => 'Business Intelligence', 'client.itsm' => 'ITSM'] as $prefix => $department) {
            if (str_starts_with($route, $prefix.'.')) return $department;
        }

        return 'ITSM';
    }
}
