<?php

namespace App\Concerns;

use App;
use App\Revision;
use Auth;
use Illuminate\Database\Eloquent\Model;
use Log;

trait Revisionable
{
    private static $pendingRevisions = [];

    public static function bootRevisionable()
    {
        static::updating(function (Model $model) {
            $to = static::filterRevisionableFields($model);
            $from = array_only($model->getOriginal(), array_keys($to));

            static::$pendingRevisions[$model->getKey()] = compact('from', 'to');
        });

        static::updated(function (Model $model) {
            $user = Auth::user();

            if (!$user && !App::runningInConsole()) {
                Log::warning('Unauthenticated user changed a revisionable model: '
                             .$model->getKey().', '.$model->getMorphClass());
            }

            if (!count(static::$pendingRevisions[$model->getKey()])) {
                return;
            }

            $revision = new Revision(static::$pendingRevisions[$model->getKey()]);

            $revision->revisionable()->associate($model);
            $revision->user()->associate($user);

            if (!$revision->save()) {
                Log::warning('Failed to record a revision.', $revision->toArray());
            }

            unset(static::$pendingRevisions[$model->getKey()]);
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return array
     */
    static private function filterRevisionableFields(Model $model)
    {
        if (method_exists($model, 'getRevisionableFields')) {
            return array_only($model->getDirty(), $model->getRevisionableFields());
        }

        if (method_exists($model, 'getNonRevisionableFields')) {
            return array_except($model->getDirty(), $model->getNonRevisionableFields());
        }

        return array_except($model->getDirty(), ['updated_at', 'created_at']);
    }

    public function revisions()
    {
        return $this->morphToMany(Revision::class, 'revisionable');
    }
}
