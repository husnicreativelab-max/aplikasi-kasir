<?php

namespace Tests\Unit;

use App\Support\WhatsAppLink;
use PHPUnit\Framework\TestCase;

class WhatsAppLinkTest extends TestCase
{
    public function test_indonesian_phone_numbers_are_normalized_for_click_to_chat(): void
    {
        $this->assertSame('6281234567890', WhatsAppLink::normalizeNumber('0812-3456-7890'));
        $this->assertSame('6281234567890', WhatsAppLink::normalizeNumber('+62 812 3456 7890'));
        $this->assertSame('6281234567890', WhatsAppLink::normalizeNumber('812 3456 7890'));
        $this->assertNull(WhatsAppLink::normalizeNumber('not a phone number'));
    }

    public function test_click_to_chat_url_encodes_message_and_rejects_invalid_numbers(): void
    {
        $this->assertSame(
            'https://wa.me/6281234567890?text=Halo%20dunia%21',
            WhatsAppLink::url('0812 3456 7890', 'Halo dunia!'),
        );
        $this->assertNull(WhatsAppLink::url('12', 'Halo'));
    }
}
