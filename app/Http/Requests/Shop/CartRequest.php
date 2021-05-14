<?php

declare(strict_types=1);

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;

abstract class CartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user() && $this->user()->is_member;
    }

    /**
     * Max quantity per variant
     *
     * @return int
     */
    public function getMaxQuantity(): int
    {
        return Config::get('gumbo.shop.max-quantity', 5);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    abstract public function rules();
}