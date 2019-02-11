<?php
namespace seregazhuk\PinterestBot\Api\Traits;
use seregazhuk\PinterestBot\Helpers\UrlBuilder;
trait EmailExistsResource
{
    use HandlesRequest;
    /**
     * Get list of available locales
     * @return array
     */
    public function getEmailExistsResource($requestOptions)
    {
        return $this->get(UrlBuilder::EMAIL_EXIST_RESOURCE, $requestOptions);
    }

}
