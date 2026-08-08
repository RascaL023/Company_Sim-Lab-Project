<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditTrail::with(['user', 'auditable']);

        if ($request->filled('action')) {
            $query->where('action', $request->get('action'));
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->get('auditable_type'));
        }

        if ($request->filled('auditable_id')) {
            $query->where('auditable_id', $request->get('auditable_id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        return $query->orderBy('created_at', 'desc')->paginate($request->query('per_page', 50));
    }

    public function show(AuditTrail $auditTrail)
    {
        return $auditTrail;
    }

    public function recent(Request $request)
    {
        return AuditTrail::with(['user', 'auditable'])
            ->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 20))
            ->get();
    }
}
