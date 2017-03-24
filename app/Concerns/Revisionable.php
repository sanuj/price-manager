<?php

namespace App\Concerns;

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
            $to = $model->getDirty();
            $from = array_only($model->getOriginal(), array_keys($to));

            static::$pendingRevisions[$model->getKey()] = compact('from', 'to');
        });

        static::updated(function (Model $model) {
            $user = Auth::user();

            if (!$user) {
                Log::warning('Unauthenticated user changed a revisionable model: '
                             .$model->getKey().', '.$model->getMorphClass());
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

    public function revisions()
    {
        return $this->morphToMany(Revision::class, 'revisionable');
    }
}
