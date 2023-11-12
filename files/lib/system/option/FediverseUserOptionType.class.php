<?php

namespace wcf\system\option;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Laminas\Diactoros\Uri;
use Throwable;
use wcf\data\option\Option;
use wcf\system\exception\SystemException;
use wcf\system\exception\UserInputException;
use wcf\system\io\HttpFactory;
use wcf\util\JSON;

final class FediverseUserOptionType extends TextOptionType
{
    private ClientInterface $httpClient;

    private string $error;

    /**
     * @inheritDoc
     */
    public function getFormElement(Option $option, $value)
    {
        try {
            $data = JSON::decode($value);
            $value = $data['value'];
        } catch (SystemException $e) {
            $value = '';
        }

        return parent::getFormElement($option, $value);
    }

    /**
     * @inheritDoc
     */
    public function validate(Option $option, $newValue)
    {
        parent::validate($option, $newValue);

        if (isset($this->error) && $this->error !== '') {
            throw new UserInputException($option->optionName, $this->error);
        }
    }

    /**
     * @inheritDoc
     */
    public function getData(Option $option, $newValue)
    {
        // Check if the value is empty
        if (empty($newValue)) {
            // Value is empty, don't encode and save
            return '';
        }

        return JSON::encode([
            'value' => $newValue,
            'href' => $this->getLink($newValue),
        ]);
    }

    private function getLink($value)
    {
        if (
            \preg_match(
                "/^[@]([a-zA-Z0-9_]+)[@]((?=[a-z0-9-]{1,63}\\.)(xn--)?[a-z0-9]+(-[a-z0-9]+)*\\.)+[a-z]{2,63}$/",
                $value
            ) !== 1
        ) {
            $this->error = 'validationFailed';

            return '';
        }

        $values = \explode('@', $value);
        if (\count($values) !== 3) {
            $this->error = 'validationFailed';

            return '';
        }

        $query = [
            'resource' => "acct:{$values[1]}@{$values[2]}",
        ];

        $url = (new Uri())
            ->withScheme('https')
            ->withHost($values[2])
            ->withPath('.well-known/webfinger')
            ->withQuery(\http_build_query($query, '', '&', \PHP_QUERY_RFC3986));
        $request = new Request('GET', $url);
        try {
            $response = $this->getHttpClient()->send($request);
            $data = JSON::decode((string)$response->getBody());
        } catch (Throwable $e) {
            \wcf\functions\exception\logThrowable($e);

            $this->error = 'userNotFound';

            return '';
        }

        if (!isset($data['links']) || !\is_array($data['links'])) {
            $this->error = 'userNotFound';

            return '';
        }

        $userLink = '';
        foreach ($data['links'] as $link) {
            if (!isset($link['rel']) || !isset($link['href']) || $link['rel'] !== 'self') {
                continue;
            }

            $userLink = $link['href'];
            break;
        }
        if ($userLink === '') {
            $this->error = 'userNotFound';

            return '';
        }

        return $userLink;
    }

    private function getHttpClient(): ClientInterface
    {
        if (!isset($this->httpClient)) {
            $this->httpClient = HttpFactory::makeClientWithTimeout(5);
        }

        return $this->httpClient;
    }
}
