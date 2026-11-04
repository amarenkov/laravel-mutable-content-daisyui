<?php

namespace Amarenkov\MutableContentDaisyUi\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;

use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Label as CFALabel;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Type as CFAType;

use Amarenkov\MutableContent\Contracts\MutableModel;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;

use Amarenkov\MutableContent\Models\Concerns\HasMutableFields;

#[ClassLabel('Note')]
class Note extends Model implements MutableModel
{
    use HasUuids;
    use HasMutableFields;

    // const
    #[CFAType(Type::TYPE_STRING), CFALabel('Body')]
    public const FIELD_BODY = 'body';

    // protected
    public $timestamps = false;
}
