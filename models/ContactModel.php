<?php

class ContactModel extends BaseModel
{
    public function create(array $contact)
    {
        $this->execute(
            'INSERT INTO contacts (full_name, email, phone, message, created_at)
             VALUES (:full_name, :email, :phone, :message, NOW())',
            [
                'full_name' => $contact['full_name'],
                'email' => $contact['email'],
                'phone' => $contact['phone'],
                'message' => $contact['message'],
            ]
        );
    }
}
