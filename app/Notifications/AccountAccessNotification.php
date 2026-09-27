<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountAccessNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $token,
        public bool $isInvitation = false,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
       return $notifiable->status === 'inactive' ? [] : ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // return (new MailMessage)
        //     ->line('The introduction to the notification.')
        //     ->action('Notification Action', url('/'))
        //     ->line('Thank you for using our application!');

        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $isInvitation = $this->isInvitation;

        return (new MailMessage)
            ->subject($isInvitation ? 'Set up your SOS Labour Solutions account' : 'Reset your password')
            ->view('emails.account-access', [
                'firstName' => $notifiable->first_name,
                'title' => $isInvitation
                    ? 'Welcome SOS Labour Solutions Workspace'
                    : 'Reset your password',
                'introText' => $isInvitation
                    ? 'We are glad to have you on board!'
                    : null,
                'bodyText' => $isInvitation
                    ? 'Please click the button below to set up your account.'
                    : 'You are receiving this email because we received a password reset request for your account.',
                'actionUrl' => $url,
                'buttonText' => $isInvitation ? 'Set Up Account' : 'Reset Password',
                'expiryText' => $isInvitation
                    ? 'This account setup link will expire in '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes.'
                    : 'This password reset link will expire in '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes.',
                'noteText' => $isInvitation
                    ? null
                    : 'If you did not request a password reset, no further action is required.',
                'closingText' => $isInvitation ? 'Cheers,' : 'Regards,',
                'brandName' => $isInvitation ? 'SOS Labour Solutions' : 'SOS Labour Solutions',
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
