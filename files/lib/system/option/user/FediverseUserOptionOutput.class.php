<?php

namespace wcf\system\option\user;

use Laminas\Diactoros\Uri;
use wcf\data\user\option\UserOption;
use wcf\data\user\User;
use wcf\util\StringUtil;

final class FediverseUserOptionOutput implements IUserOptionOutput
{
    /**
     * @inheritDoc
     */
    public function getOutput(User $user, UserOption $option, $value)
    {
        if ($value === null || $value === '') {
            return '';
        }

        $values = \explode('@', $value);
        if (\count($values) !== 3) {
            return '';
        }

        $url = (new Uri())
            ->withScheme('https')
            ->withHost($values[2])
            ->withPath('@' . $values[1]);

        return StringUtil::getAnchorTag((string)$url, $value);
    }
}
