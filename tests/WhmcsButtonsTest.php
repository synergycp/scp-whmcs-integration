<?php

use Scp\Whmcs\Whmcs\WhmcsButtons;

class WhmcsButtonsTest extends TestCase
{
    public function testLoginLinkHrefEscapesQueryValues()
    {
        $payload = '"><script>alert(1)</script>';
        $href = WhmcsButtons::loginLinkHref('login_client', [
            'userid' => '5',
            'id' => '7',
            'extra' => $payload,
            'login_admin' => '',
        ]);

        $this->assertStringStartsWith('?', $href);
        $this->assertStringNotContainsString('<', $href);
        $this->assertStringNotContainsString('>', $href);
        $this->assertStringNotContainsString('"', $href);
        $this->assertStringNotContainsString('login_admin', $href);

        parse_str(substr(html_entity_decode($href, ENT_QUOTES, 'UTF-8'), 1), $parsed);
        $this->assertSame('7', $parsed['id']);
        $this->assertSame('5', $parsed['userid']);
        $this->assertSame($payload, $parsed['extra']);
        $this->assertArrayHasKey('login_client', $parsed);
        $this->assertArrayNotHasKey('login_admin', $parsed);
    }

    public function testLoginLinkHrefReplacesPreviousFlag()
    {
        $href = WhmcsButtons::loginLinkHref('login_admin', ['id' => '1', 'login_client' => '']);

        parse_str(substr(html_entity_decode($href, ENT_QUOTES, 'UTF-8'), 1), $parsed);
        $this->assertSame(['id' => '1', 'login_admin' => ''], $parsed);
    }

    public function testTransferMarkupEscapesUrl()
    {
        $url = 'https://scp.example/auth/sso?key=abc"></script><script>alert(1)</script>';
        $markup = WhmcsButtons::transferMarkup($url, 'Synergy<CP>');

        $this->assertStringNotContainsString('</script><script>', $markup);
        $this->assertStringNotContainsString('href="' . $url, $markup);
        $this->assertStringNotContainsString('Synergy<CP>', $markup);
        $this->assertStringContainsString('window.location.href=', $markup);
        $this->assertStringContainsString(htmlspecialchars($url, ENT_QUOTES, 'UTF-8'), $markup);
        $this->assertStringContainsString('Synergy&lt;CP&gt;', $markup);

        // The script assignment must still decode back to the original URL.
        preg_match('/window\.location\.href=(.+?)<\/script>/', $markup, $match);
        $this->assertSame($url, json_decode($match[1]));
    }
}
