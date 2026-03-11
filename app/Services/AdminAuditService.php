<?php

namespace App\Services;

use App\Models\AdminActionLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AdminAuditService
{
    public function record(?User $admin, string $action, Model|string|null $target, string $description, array $context = []): AdminActionLog
    {
        [$targetType, $targetId, $targetLabel] = $this->resolveTarget($target);

        return AdminActionLog::query()->create([
            'admin_user_id' => $admin?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'target_label' => $targetLabel,
            'description' => $description,
            'context' => $context !== [] ? $context : null,
        ]);
    }

    private function resolveTarget(Model|string|null $target): array
    {
        if ($target instanceof Model) {
            return [
                class_basename($target::class),
                (int) $target->getKey(),
                $this->resolveLabel($target),
            ];
        }

        if (is_string($target) && $target !== '') {
            return [$target, null, null];
        }

        return [null, null, null];
    }

    private function resolveLabel(Model $target): ?string
    {
        foreach (['protocolo', 'nome', 'name', 'title', 'email', 'slug'] as $attribute) {
            $value = $target->getAttribute($attribute);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
