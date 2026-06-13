<?php

namespace App\Mail;

use App\Models\Attempt;
use App\Models\Exam;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExamResultsReleasedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{question: string, student_answer: string, correct_answer: string, result: string}>  $reviewItems
     * @param  list<array{question: string, student_answer: string, correct_answer: string}>  $wrongItems
     */
    public function __construct(
        public Exam $exam,
        public Attempt $attempt,
        public array $wrongItems,
        public array $reviewItems = []
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Exam Results Released: '.$this->exam->title
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.exam-results-released',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
