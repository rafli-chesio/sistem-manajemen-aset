<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Record an audit entry.
     *
     * @param string      $action      e.g. 'asset.created', 'borrow.approved'
     * @param object|null $entity      The related Eloquent model (optional)
     * @param array       $metadata    Additional context data
     */
    public function log(string $action, ?object $entity = null, array $metadata = []): AuditLog
    {
        return AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => $action,
            'entity_type' => $entity ? get_class($entity) : null,
            'entity_id'   => $entity ? $entity->getKey() : null,
            'metadata'    => $metadata,
            'ip_address'  => Request::ip(),
        ]);
    }
}
