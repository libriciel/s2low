<?php

namespace S2lowLegacy\Class;

class UserToEmailAdressConverter
{
    /**
     * Converts a user array to an email like "<givenname name> e@ma.il"
     * @param array $recipientUser
     * @return string
     */
    public function getNormalizedEmailAdresse(array $recipientUser): string
    {
        assert(!!$recipientUser["email"]);

        $recip = "";
        if (! empty($recipientUser['givenname'])) {
            $recip .= $recipientUser["givenname"] . " ";
        }
        if (! empty($recipientUser['name'])) {
            $recip .=  $recipientUser["name"];
        }
        if ($recip) {
            $recip .= " <" . $recipientUser["email"] . ">";
        } else {
            $recip = $recipientUser['email'];
        }
        return $recip;
    }
}
