<?php

namespace App\Support;

/** Starting-point legal texts for selling digital education. They must be reviewed before launch. */
class LegalTemplates
{
    public const NOTICE = '<p><em>Template text — have it reviewed by a legal adviser and adapt it (business name, address, VAT number, governing law) before you start selling.</em></p>';

    public static function termsOfSale(): string
    {
        return self::NOTICE.'
<h2>Seller</h2><p>Courses, memberships and webinar tickets on this website are sold by the owner named on the About page ([business name, address, VAT number]). Payments are processed securely by Stripe.</p>
<h2>Products</h2><ul><li><strong>Membership</strong> — a subscription billed monthly or yearly in advance that gives access to members-only content, the courses marked “included in membership” and member places at webinars, for as long as the subscription is active.</li><li><strong>Courses</strong> — a one-time purchase giving personal, non-transferable access to the course for as long as the course is offered on this website.</li><li><strong>Webinar tickets</strong> — a personal place at a live online session on the stated date, plus access to the recording where one is published.</li></ul>
<h2>Prices and VAT</h2><p>Prices are shown in the stated currency and include VAT where applicable. Business customers may enter a VAT number at checkout. An invoice or receipt is sent by email for every payment.</p>
<h2>Renewal and cancellation of membership</h2><p>Memberships renew automatically at the end of each billing period until cancelled. You can cancel at any time under Account › Billing; access continues until the end of the period already paid for. We will tell you in advance about any price change.</p>
<h2>Right of withdrawal (EU/UK consumers)</h2><p>You normally have 14 days to withdraw from a purchase of digital content. When you ask for access to begin immediately and confirm that you understand you will lose this right, the right of withdrawal ends when access begins. Please see our refund policy for the refunds we offer voluntarily.</p>
<h2>Use of content</h2><p>Access is personal. Sharing your login, recording or redistributing lessons or webinars is not permitted. All content is educational and does not replace clinical judgement.</p>
<h2>Availability</h2><p>We aim to keep the service available at all times but cannot guarantee uninterrupted access. If a paid webinar is cancelled, you will receive a full refund or a place at the rescheduled session.</p>
<h2>Contact and complaints</h2><p>Please use the contact form for questions about orders, invoices or refunds. EU consumers can also use the EU Online Dispute Resolution platform.</p>';
    }

    public static function refundPolicy(): string
    {
        return self::NOTICE.'
<h2>Courses</h2><p>If a course is not right for you, contact us within 14 days of purchase and before you have completed more than 25% of the lessons, and we will refund you in full.</p>
<h2>Membership</h2><p>You can cancel at any time and keep access until the end of the current period. Payments for a period that has started are not refunded, except where required by law. If you forget to cancel before a yearly renewal, contact us within 7 days of the renewal and we will refund it.</p>
<h2>Webinars</h2><p>Tickets can be refunded up to 48 hours before the start. If we cancel or reschedule a webinar, you can choose a full refund.</p>
<h2>How refunds are made</h2><p>Refunds are made to the original payment method, usually within 5–10 working days. Access bought with a refunded payment ends when the refund is issued.</p>';
    }
}
