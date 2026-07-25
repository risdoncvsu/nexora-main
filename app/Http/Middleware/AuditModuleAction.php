<?php

namespace App\Http\Middleware;

use App\Services\ErpIntegrationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        $clientId = (int) (session('employee_client_id') ?: $request->attributes->get('ecommerce_company')?->id ?: $request->user()?->company_id);
        $department = $this->department($request);
        $actor = session('employee_name') ?: $request->user()?->username ?: $request->user()?->email;
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true) || $response->getStatusCode() >= 400) {
            return $response;
        }

        if ($clientId <= 0) {
            return $response;
        }

        try {
            app(ErpIntegrationService::class)->recordAudit($clientId, 'action.'.strtolower($request->method()), $department, [
                'route' => $request->route()?->getName(),
                'path' => '/'.$request->path(),
                'actor' => $actor,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('ERP audit logging failed after a completed request.', [
                'route' => $request->route()?->getName(),
                'exception' => $exception->getMessage(),
            ]);
        }

        return $response;
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
