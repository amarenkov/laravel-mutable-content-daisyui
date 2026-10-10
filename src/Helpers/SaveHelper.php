<?php

namespace Amarenkov\MutableContentDaisyUi\Helpers;

use Closure;
use DomainException;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

use Amarenkov\MutableContentDaisyUi\Exceptions\SaveFailed;

class SaveHelper
{
    /**
     * Run a save in its own transaction; a unique violation or a DomainException becomes SaveFailed with a message for the user.
     *
     * @throws SaveFailed
     */
    public static function save(Closure $callback): mixed
    {
        try {
            return DB::transaction($callback);
        } catch (UniqueConstraintViolationException) {
            throw new SaveFailed(
                __('mutable-content-daisyui::ui.save.duplicate'),
                __('mutable-content-daisyui::ui.save.duplicate_body')
            );
        } catch (DomainException $exception) {
            throw new SaveFailed(__('mutable-content-daisyui::ui.save.failed'), $exception->getMessage());
        }
    }
}
