<?php

namespace App\Mail\Join;

use App\Models\JoinSubmission;

/**
 * Email sent to the new member concerning his enrollment
 *
 * @author Roelof Roos <github@roelof.io>
 * @license MPL-2.0
 */
class UserJoinMail extends BaseMail
{
    /**
     * @inheritDoc
     */
    public function build()
    {
        return $this->markdown('mail.join.user');
    }

    /**
     * @inheritDoc
     */
    protected function createSubject(JoinSubmission $submission): string
    {
        return '🎉 Welkom bij Gumbo Millennium 🎉';
    }
}