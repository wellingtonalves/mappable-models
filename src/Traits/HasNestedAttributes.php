<?php

namespace Reddes\MappableModels\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use Psr\Log\InvalidArgumentException;

trait HasNestedAttributes
{
    /**
     * Defined nested attributes
     *
     * @var array
     */
    protected $acceptNestedAttributesFor = [];

    /**
     * Defined "destroy" key name
     *
     * @var string
     */
    protected $destroyNestedKey = '_destroy';


    /**
     * Get accept nested attributes
     *
     * @return array
     */
    public function getAcceptNestedAttributesFor()
    {
        return $this->acceptNestedAttributesFor;
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param  array $attributes
     * @return $this
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function fill(array $attributes)
    {
        if (!empty($this->nested)) {
            $this->acceptNestedAttributesFor = [];

            foreach ($this->nested as $attr) {
                if (isset($attributes[$attr])) {
                    $this->acceptNestedAttributesFor[$attr] = $attributes[$attr];
                }
            }

            if (!empty($this->guarded) || empty($this->guarded) && empty($this->fillable)) {
	        	$this->guarded = array_merge($this->guarded, $this->nested);
	        }
        }

        return parent::fill($attributes);
    }

    /**
     * Save the model to the database.
     *
     * @param  array $options
     * @return bool
     */
    public function save(array $options = [])
    {
        DB::beginTransaction();

        if (!parent::save($options)) {
            return false;
        }

        foreach ($this->getAcceptNestedAttributesFor() as $attribute => $stack) {
            $methodName = lcfirst(join(array_map('ucfirst', explode('_', $attribute))));

            if (!method_exists($this, $methodName)) {
                throw new InvalidArgumentException('The nested atribute relation "' . $methodName . '" does not exists.');
            }

            $relation = $this->$methodName();

            if ($relation instanceof BelongsTo) {
                if (!$this->saveBelongToNestedAttributes($relation, $stack)) {
                    return false;
                }
            } else {
                if ($relation instanceof HasOne || $relation instanceof MorphOne) {
                    if (!$this->saveOneNestedAttributes($relation, $stack)) {
                        return false;
                    }
                } else {
                    if ($relation instanceof HasMany || $relation instanceof MorphMany) {

                        $idsArray      = array_map(function ($stack) {
                            return isset($stack[$this->primaryKey]) ? $stack[$this->primaryKey] : false;
                        }, $stack);
                        $idsNotDelete  = array_filter($idsArray);
                        $modelRelation = $this->$methodName();

                        //Syncing one-to-many relationships
                        if (count($idsNotDelete) > 0) {
                            $modelRelation->whereNotIn($this->primaryKey, $idsNotDelete)->delete();
                        } else {
                            $modelRelation->delete();
                        }

                        foreach ($stack as $params) {
                            if (!$this->saveManyNestedAttributes($this->$methodName(), $params)) {
                                return false;
                            }
                        }

                    } else {

                        if ($relation instanceof BelongsToMany) {

                            $idsNesteds = [];
                            foreach ($stack as $params) {
                                $idsNesteds[] = $this->saveBelongsToManyNestedAttributes($this->$methodName()->getModel(),
                                    $params);
                            }

                            $this->$methodName()->sync($idsNesteds);
                        } else {
                            throw new InvalidArgumentException('The nested atribute relation is not supported for "' . $methodName . '".');
                        }
                    }
                }
            }
        }

        DB::commit();

        return true;
    }

    /**
     * Save the hasOne nested relation attributes to the database.
     *
     * @param  Illuminate\Database\Eloquent\Relations $relation
     * @param  array $params
     * @return bool
     */
    protected function saveBelongToNestedAttributes($relation, array $params)
    {
        if ($this->exists && $model = $relation->first()) {
            if ($this->allowDestroyNestedAttributes($params)) {
                return $model->delete();
            }
            return $model->update($params);
        } else {
            if ($related = $relation->create($params)) {
                $belongs = $relation->getRelation();
                $this->$belongs()->associate($related);
                parent::save();

                return true;
            }
        }
        return false;
    }

    /**
     * Save the hasOne nested relation attributes to the database.
     *
     * @param  Illuminate\Database\Eloquent\Relations $relation
     * @param  array $params
     * @return bool
     */
    protected function saveOneNestedAttributes($relation, array $params)
    {
        if ($this->exists && $model = $relation->first()) {
            if ($this->allowDestroyNestedAttributes($params)) {
                return $model->delete();
            }
            return $model->update($params);
        } else {
            if ($relation->create($params)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Save the hasMany nested relation attributes to the database.
     *
     * @param  Illuminate\Database\Eloquent\Relations $relation
     * @param  array $params
     * @return bool
     */
    protected function saveManyNestedAttributes($relation, array $params)
    {
        $keyName = $relation->getModel()->getKeyName();

        if (isset($params[$keyName]) && $this->exists) {
            $model = $relation->findOrFail($params[$keyName]);

            if ($this->allowDestroyNestedAttributes($params)) {
                return $model->delete();
            }
            return $model->update($params);
        } else {
            if ($relation->create($params)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Save the belongsToMany nested relation attributes to the database.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  array $params
     * @return bool
     */
    protected function saveBelongsToManyNestedAttributes($model, array $params)
    {
        $keyName = $model->getKeyName();

        $attributes = !empty($params[$keyName]) ? [$keyName => $params[$keyName]] : $params;

        if ($model->nested) {
            foreach ($model->nested as $nested) {
                unset($attributes[$nested]);
            }
        }

        $params = $model::updateOrCreate($attributes, $params);
        return $params->$keyName;
    }

    /**
     * Check can we delete nested data
     *
     * @param  array $params
     * @return bool
     */
    protected function allowDestroyNestedAttributes(array $params)
    {
        return isset($params[$this->destroyNestedKey]) && (bool)$params[$this->destroyNestedKey] === true;
    }
}
