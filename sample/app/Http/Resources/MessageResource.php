<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function toArray($request) {
        return [
            'status'        =>      $this->resource['status'] === 'success' ? 'success' : 'failed',
            'data'          =>      [
                'message'       =>      [
                        'title'     =>      $this->resource['title'],
                        'text'      =>      $this->resource['text'] ?? '',
                        'icon'      =>      $this->resource['status'],
                        'button'    =>      [
                            'text'          =>  __('general.okay'),
                            'closeModal'    =>  true
                        ]
                ]
            ]
        ];
    }
}
