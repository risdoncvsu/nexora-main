<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RiskController;
use App\Http\Controllers\RiskMitigationController;
use App\Http\Controllers\IncidentController; 
use App\Http\Controllers\RiskAnalyticsController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\AuditController; 
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\PermitController;
use App\Http\Controllers\RiskAssController;
use App\Http\Controllers\DocumentController; // Imported DocumentController
use App\Http\Controllers\NewUserSetupController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RolesAndPermissionController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\EmployeePortalController;
use App\Http\Controllers\ContactController;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

// Public company-contact page linked from the primary sign-in screen.
Route::view('/contact', 'contact')->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// Company logos are public brand assets. Streaming from the public disk avoids
// broken module/storefront logos when a deployment has no storage symlink.
Route::get('/company-logo/{company}', function (Company $company) {
    abort_unless($company->logo_path && Storage::disk('public')->exists($company->logo_path), 404);

    return Storage::disk('public')->response($company->logo_path, null, [
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('company.logo');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email');
Route::get('/first-login/password', [AuthController::class, 'showHrFirstLoginPassword'])->name('hr.first-login.password');
Route::post('/first-login/password', [AuthController::class, 'storeHrFirstLoginPassword'])->name('hr.first-login.password.store');

// Employee accounts are authenticated from HR but land in this ITSM extension
// first, where they can enter their assigned module or contact their client ITSM team.
Route::middleware('employee.portal')->prefix('employee')->name('employee.')->group(function () {
    Route::get('/portal', [EmployeePortalController::class, 'index'])->name('portal');
    Route::post('/support-tickets', [EmployeePortalController::class, 'storeTicket'])->name('support-tickets.store');
});

Route::middleware('auth')->group(function () {

    Route::middleware('client.admin')->group(function () {
        Route::get('/newuser', [NewUserSetupController::class, 'show'])->name('newuser.show');
        Route::post('/newuser/password', [NewUserSetupController::class, 'storePassword'])->name('newuser.password');
        Route::post('/newuser/logo', [NewUserSetupController::class, 'storeLogo'])->name('newuser.logo');
        Route::post('/newuser/hr-manager', [NewUserSetupController::class, 'storeHrManager'])->name('newuser.hr-manager');
    });

    Route::get('/dashboard', function () {
        return redirect()->route('admin.itsm.registration');
    })->middleware('root.admin')->name('dashboard');

    // ==========================================
    // ADMIN ITSM ROUTES
    // ==========================================
    Route::middleware('root.admin')->prefix('admin/itsm')->name('admin.itsm.')->group(function () {
        Route::get('/registration', function () {
            return view('dashboard', ['clientLocales' => config('client_locales.countries', [])]);
        })->name('registration');

        Route::post('/registration', [CompanyController::class, 'store'])->name('registration.store');
        Route::get('/clients', [UserController::class, 'clients'])->name('clients');
        Route::patch('/clients/{company}', [CompanyController::class, 'update'])->name('clients.update');
        Route::delete('/clients/{company}', [CompanyController::class, 'destroy'])->name('clients.destroy');

        Route::get('/service-desk', [TicketController::class, 'index'])->defaults('portal', 'admin')->name('service-desk');
        Route::patch('/service-desk/{ticket}', [TicketController::class, 'update'])->name('service-desk.update');
        Route::get('/service-desk/assigned', [TicketController::class, 'assignedIndex'])->name('service-desk.assigned');
        Route::patch('/service-desk/{ticket}/claim', [TicketController::class, 'claim'])->name('service-desk.claim');
        Route::patch('/service-desk/{ticket}/release', [TicketController::class, 'release'])->name('service-desk.release');
        Route::get('/service-desk/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('service-desk.knowledge-base');
        Route::post('/service-desk/knowledge-base', [KnowledgeBaseController::class, 'store'])->name('service-desk.knowledge-base.store');
        Route::get('/pending-approvals', [UserController::class, 'pending'])->name('pending-approvals');
        Route::get('/roles-permissions', [RolesAndPermissionController::class, 'index'])->name('roles');
        Route::get('/audit-trail', [AuditTrailController::class, 'rootIndex'])->name('audit-trail');
        Route::get('/audit-trail/export', [AuditTrailController::class, 'export'])->name('audit-trail.export');
    });

    // ==========================================
    // CLIENT ITSM ROUTES
    // ==========================================
    Route::middleware('client.admin')->prefix('client/itsm')->name('client.itsm.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('client.itsm.employees');
        })->name('dashboard');

        Route::get('/employees', [UserController::class, 'employees'])->name('employees');
        Route::patch('/employees/{employee}', [UserController::class, 'updateEmployee'])->name('employees.update');
        Route::get('/pending-approvals', [UserController::class, 'pendingApprovals'])->name('pending-approvals');
        Route::post('/pending-approvals/{employee}/approve', [UserController::class, 'approveHrManager'])->name('pending-approvals.approve');
        Route::get('/audit-trail', [AuditTrailController::class, 'index'])->name('audit-trail');
        Route::get('/audit-trail/export', [AuditTrailController::class, 'export'])->name('audit-trail.export');

        Route::get('/service-desk', [TicketController::class, 'index'])->name('service-desk');
        Route::post('/service-desk', [TicketController::class, 'store'])->name('service-desk.store');
        Route::patch('/service-desk/{ticket}', [TicketController::class, 'update'])->name('service-desk.update');
        Route::get('/service-desk/support', [TicketController::class, 'supportIndex'])->name('service-desk.support');
        Route::post('/service-desk/support', [TicketController::class, 'store'])->name('service-desk.support.store');
        Route::post('/service-desk/support/{ticket}/reset-password', [PasswordResetController::class, 'process'])->name('service-desk.support.reset-password');
        // The ticket dashboard already exposes resolved status. Keep old bookmarks working
        // without keeping a second, error-prone Resolved Tickets screen in the client UI.
        Route::get('/service-desk/resolved-tickets', fn () => redirect()->route('client.itsm.service-desk'))->name('service-desk.resolvedtickets');
        Route::get('/service-desk/knowledge-base', [ServiceController::class, 'knowledgeBase'])->name('service-desk.knowledgebase');
        Route::post('/service-desk/knowledge-base', [KnowledgeBaseController::class, 'storeClient'])->name('service-desk.knowledgebase.store');
        
        // ==========================================
        // COMPLIANCE MODULE ROUTES
        // ==========================================
        Route::get('/compliance', [ComplianceController::class, 'index'])->name('compliance');
        Route::post('/compliance/store', [ComplianceController::class, 'store'])->name('compliance.store');
        Route::get('/compliance/files/{path}', [ComplianceController::class, 'file'])->where('path', '.*')->name('compliance.file');
        
        // Audits are captured in the client audit trail. Redirect legacy URLs there.
        Route::get('/audit', fn () => redirect()->route('client.itsm.audit-trail'))->name('audit');
        Route::post('/audit', fn () => redirect()->route('client.itsm.audit-trail'))->name('audit.store');
        
        Route::get('/permit', [PermitController::class, 'index'])->name('permit');
        Route::post('/permit', [PermitController::class, 'index'])->name('permit.store');
        Route::get('/permit/files/{path}', [PermitController::class, 'file'])->where('path', '.*')->name('permit.file');
        
        Route::get('/risk-assessment', [RiskAssController::class, 'index'])->name('risk.assessment');
        Route::post('/risk-assessment/store', [RiskAssController::class, 'store'])->name('risk.assessment.store');
        
        // BOUND TO CONTROLLER: Connected to DocumentController for functional filtering, search, and dynamic layout
        Route::get('/documents', [DocumentController::class, 'index'])->name('document');
        Route::post('/documents/store', [DocumentController::class, 'store'])->name('document.store');
        Route::get('/documents/{document}/file', [DocumentController::class, 'file'])->name('document.file');

        // Risk Management (Risk Register)
        Route::get('/risk', [RiskController::class, 'index'])->name('risk');
        Route::post('/risk/store', [RiskController::class, 'store'])->name('risk.store');
        Route::patch('/risk/{risk}', [RiskController::class, 'update'])->name('risk.update');
        Route::get('/risk/{risk}/manage', [RiskController::class, 'manage'])->name('risk.manage');
        
        // Risk Management (Mitigation Plans)
        Route::get('/risk/mitigation', [RiskMitigationController::class, 'index'])->name('risk.mitigation');
        Route::post('/risk/mitigation/store', [RiskMitigationController::class, 'store'])->name('risk.mitigation.store');
        
        // Risk Management (Incident Reports)
        Route::get('/risk/incident', [IncidentController::class, 'index'])->name('risk.incident');
        Route::post('/risk/incident/store', [IncidentController::class, 'store'])->name('risk.incident.store');
        Route::post('/risk/incident/{id}/status', [IncidentController::class, 'updateStatus'])->name('risk.incident.status');
        
        // Risk Management Analytics Console Engine
        Route::get('/risk/analytics', [RiskAnalyticsController::class, 'index'])->name('risk.analytics');
        Route::get('/risk/analytics/export', [RiskAnalyticsController::class, 'export'])->name('risk.analytics.export');
    });

    Route::get('/users', [UserController::class, 'employees'])->name('users.index');
});

Route::get('/', function () {
    return redirect()->route('login');
});



// Legacy root-admin URLs are retained for bookmarked pages, but must never be
// reachable by an unauthenticated user or a client system administrator.
Route::middleware(['auth', 'root.admin'])->group(function () {
Route::get('/users/index', function () {
    return view('users.index');
})->name('users.index');

Route::get('/users/roles', [RolesAndPermissionController::class, 'index'])
    ->name('users.roles');

Route::post('/roles/bulk-delete', [RolesAndPermissionController::class, 'bulkDelete'])
    ->name('roles.bulk-delete');

Route::post('/roles/store', [RolesAndPermissionController::class, 'store'])
    ->name('roles.store');

Route::patch('/roles/{role}', [RolesAndPermissionController::class, 'update'])
    ->name('roles.update');

Route::delete('/roles/{role}', [RolesAndPermissionController::class, 'destroy'])
    ->name('roles.destroy');



Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/pending', [UserController::class, 'pending'])->name('users.pending');


Route::post('/approvals/bulk-handle', [ApprovalController::class, 'bulkHandle'])
    ->name('approvals.bulk-handle');

    Route::get('/service/resolved-tickets', [ServiceController::class, 'resolvedTickets'])
    ->name('service.resolvedtickets');


Route::get('/service/knowledge-base', [ServiceController::class, 'knowledgeBase'])
    ->name('service.knowledgebase');
});


// This fallback lets the web audit middleware record authenticated 404s with
// the same client scope as the request that caused them.
Route::fallback(function () {
    abort(404);
})->name('system.not-found');
