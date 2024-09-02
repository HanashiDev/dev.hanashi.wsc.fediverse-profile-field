<?php

namespace wcf\system\option\user;

use Override;
use wcf\data\user\option\UserOption;
use wcf\data\user\User;
use wcf\system\exception\SystemException;
use wcf\util\JSON;
use wcf\util\StringUtil;

final class FediverseUserOptionOutput implements IUserOptionOutput
{
    #[Override]
    public function getOutput(User $user, UserOption $option, $value)
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            $data = JSON::decode($value);

            return StringUtil::getAnchorTag($data['href'], $data['value']);
        } catch (SystemException) {
            return '';
        }
    }
}
