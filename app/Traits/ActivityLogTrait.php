<?php

namespace App\Traits;
trait ActivityLogTrait
{
    protected function logActivity(string $message, array $properties = [], string $event = null)
    {
        activity()
            ->on(auth()->user())
            ->withProperties($properties)
            ->event($event)
            ->log($message);
    }   

    protected function logActivityOn($model, string $message, array $properties = [], string $event = 'created')
    {
        activity()
            ->performedOn($model)       // Sets subject_type to the model (Transaction, etc.)
            ->causedBy(auth()->user())  // Sets causer_type to User
            ->withProperties($properties)
            ->event($event)
            ->log($message);
    }
}