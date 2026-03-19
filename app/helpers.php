<?php

use App\Models\ActivityLog;

if (!function_exists('activity')) {
    function activity()
    {
        return new class {
            private $user;
            private $description;
            private $modelType;
            private $modelId;
            private $properties = [];

            public function causedBy($user)
            {
                $this->user = $user;
                return $this;
            }

            public function performedOn($model)
            {
                $this->modelType = get_class($model);
                $this->modelId = $model->id;
                return $this;
            }

            public function withProperties(array $properties)
            {
                $this->properties = $properties;
                return $this;
            }

            public function log(string $description)
            {
                return ActivityLog::create([
                    'user_id' => $this->user ? $this->user->id : null,
                    'action' => $this->extractAction($description),
                    'model_type' => $this->modelType,
                    'model_id' => $this->modelId,
                    'description' => $description,
                    'properties' => $this->properties,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }

            private function extractAction($description)
            {
                $description = strtolower($description);
                if (str_contains($description, 'created')) return 'created';
                if (str_contains($description, 'updated')) return 'updated';
                if (str_contains($description, 'deleted')) return 'deleted';
                if (str_contains($description, 'logged in')) return 'login';
                if (str_contains($description, 'logged out')) return 'logout';
                if (str_contains($description, 'registered')) return 'registered';
                return 'other';
            }
        };
    }
}