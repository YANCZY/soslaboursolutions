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
    public function __construct(public string $token,)
    {
        //
    }

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

        $isPending = $notifiable->status === 'pending';

        return (new MailMessage)
            ->subject($isPending ? 'Set up your SOS Solutions account' : 'Reset your password')
            ->view('emails.account-access', [
                'firstName' => $notifiable->first_name,
                'title' => $isPending
                    ? 'Welcome SOS Solutions Workspace'
                    : 'Reset your password',
                'introText' => $isPending
                    ? 'We are glad to have you on board!'
                    : null,
                'bodyText' => $isPending
                    ? 'Please click the button below to set up your account.'
                    : 'You are receiving this email because we received a password reset request for your account.',
                'actionUrl' => $url,
                'buttonText' => $isPending ? 'Set Up Account' : 'Reset Password',
                'expiryText' => $isPending
                    ? 'This account setup link will expire in '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes.'
                    : 'This password reset link will expire in '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes.',
                'noteText' => $isPending
                    ? null
                    : 'If you did not request a password reset, no further action is required.',
                'closingText' => $isPending ? 'Cheers,' : 'Regards,',
                'brandName' => $isPending ? 'SOS Solutions' : 'SOS Labour Solutions',
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
