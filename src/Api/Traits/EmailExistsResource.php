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
    public function getLoginEmailExistsResource($requestOptions)
    {
        return $this->get(UrlBuilder::LOGIN_EMAIL_EXIST_RESOURCE, $requestOptions);
    }

}
