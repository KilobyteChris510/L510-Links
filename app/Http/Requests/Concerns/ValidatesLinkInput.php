<?php

namespace App\Http\Requests\Concerns;

trait ValidatesLinkInput
{
    /**
     * Prefix https:// only when the URL has no scheme and starts with something that
     * looks like a domain ("example.com/page"). URLs with any scheme are left untouched,
     * so unsupported schemes such as javascript: are rejected rather than rewritten.
     */
    protected function prepareForValidation(): void
    {
        $url = $this->input('url');

        if (is_string($url)
            && ! preg_match('/^[a-z][a-z0-9+.\-]*:/i', $url)
            && preg_match('/^[^\s\/?#:]+\.[a-z]{2,}(?:[\/?#]|$)/i', $url)) {
            $this->merge(['url' => 'https://'.$url]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.url' => 'Enter a valid web address starting with http:// or https://.',
        ];
    }
}
