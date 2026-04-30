<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        Template::query()->firstOrCreate(
            [
                'slug' => 'proposal',
                'language' => 'en',
            ],
            [
                'title' => 'Proposal',
                'type' => Template::TYPE_HTML,
                'content' => <<<'HTML'
<h1>Proposal</h1>

<h2>Conditions</h2>
<p>To proceed with the confirmation:</p>
<ul>
    <li>A non-refundable deposit of 2000 euros is required upon confirmation of the wedding planning package.</li>
    <li>Second deposit of 4000 euros is due 6 months before the wedding day.</li>
    <li>Balance is due by the wedding day.</li>
    <li>Payments are accepted in cash, Wise or bank transfer.</li>
    <li>In case of postponements due to Covid-19 or force majeure, deposits paid will be used as credit to reschedule the event.</li>
    <li>Our travel fees are included on us for maximum 2 trips to the designated region/venue, usually for site inspections and meetings with the couple or suppliers and for the wedding day. For additional trips, extra travel fees apply.</li>
    <li>During the event(s), staff meals and water are required for the planner and assistant(s).</li>
</ul>

<h2>Offer validity</h2>
<p>
    This offer is valid 30 days from today
    <strong>(until {{ proposal_valid_until }})</strong>.
    After that limit, a new quote might apply.
</p>

<p><strong>No reservation has been made at this stage.</strong></p>
HTML,
            ],
        );

        Template::query()->firstOrCreate(
            [
                'slug' => 'contract',
                'language' => 'en',
            ],
            [
                'title' => 'Contract',
                'type' => Template::TYPE_HTML,
                'content' => <<<'HTML'
<h1>Wedding Planner Contract Fairytale Italy Weddings</h1>

<p>
    This Wedding Planner Agreement (the “Contract”) is entered into
    <strong>{{ contract_sent_at }}</strong> (the “Effective Date”), by and between
    <strong>{{ partner_one_name }} {{ partner_two_name }}</strong>, with an address of
    <strong>{{ address }}</strong> (the “Client”) and
    <strong>Fairytale Italy Weddings by Irene Cencetti</strong>, with an address of
    Via Montefiano 15, 50014 Fiesole, Italy - VAT n° 07153950485 (the “Planner”),
    also individually referred to as “Party”, and collectively “the Parties.”
</p>

<h2>1. Wedding Details</h2>
<p><strong>Dates:</strong> from {{ event_start_date }} to {{ event_end_date }}</p>
<p><strong>Wedding Date:</strong> {{ wedding_date }}</p>
<p><strong>Ceremony Location:</strong> {{ ceremony_location }}</p>
<p><strong>Reception Location:</strong> {{ reception_location }}</p>
<p><strong>Estimated Timings:</strong> {{ estimated_timings }}</p>
<p><strong>Estimated n° of Guests:</strong> around {{ estimated_guest_count }} guests</p>

<h2>2. Planner Duties</h2>
<p>The Client engages the Planner’s services to perform the following duties with regards to the event:</p>
<ol>
    <li>Unlimited meetings and consultations via email and/or telephone/videocalls with the couple.</li>
    <li>Planner’s Telephone Number: 0039 353 4171643</li>
    <li>Planner’s Email Address: hello@fairytaleitalyweddings.com</li>
    <li>Client’s Telephone Number: {{ primary_phone }} {{ secondary_phone }}</li>
    <li>Client’s Email Address: {{ reference_email }}</li>
</ol>

<p>
    The Planner is available for calls and typically answers to Clients’ emails from Monday to Friday
    from 9 am to 12 pm and from 2 pm to 7 pm Italy time. During weekends, she can be available at
    her own discretion. The Planner guarantees replies to emails within 24/48 hours maximum. During
    wedding days, she cannot guarantee quick answers but will be back as soon as possible.
</p>

<p>
    During the whole planning process, the Planner will interface with the Client stated in this Contract.
    Client’s relatives or friends will not be welcomed to contact the Planner to interfere in what has been
    or will be planned directly with the couple.
</p>

<ol start="2">
    <li>Assistance with an estimated budget and breakdown of costs.</li>
    <li>Meetings with the couple before the wedding for trials, meetings with suppliers and food tastings.</li>
    <li>Assistance with guest accommodation at the wedding venue and presence upon guests arrival on {{ event_start_date }} and on the following day.</li>
    <li>Help with the symbolic ceremony at the venue.</li>
    <li>Find all suppliers and/or service providers for your event.</li>
    <li>Management and coordination with reception location personnel and vendors involved in the events.</li>
    <li>Suggestions on aesthetics of ceremony and dinner décor, lighting, table set-up, stationery, etc.</li>
    <li>Provide a detailed timeline to suppliers and/or service providers and bridal party.</li>
    <li>Confirmation and follow-up with all suppliers and/or service providers 1-2 weeks prior to Wedding Day.</li>
    <li>Reminder of due payments to vendors.</li>
    <li>A spreadsheet will be shared on Google Drive with the Client and updated as suppliers are booked, with estimated costs and payment terms.</li>
    <li>Day of coordination and supervision of 1 wedding planner and 1 assistant from the bridal getting ready until max 1 am on the wedding day. Extra costs might be incurred for extra hours.</li>
    <li>Planning and coordination of the pre and post wedding activities listed in {{ additional_events }}.</li>
</ol>

<h2>3. Payment</h2>
<p>The Parties agree to the following Payment and Payment Terms:</p>
<ul>
    <li><strong>Total Fee for the services:</strong> {{ contract_total_fee }}</li>
    <li><strong>First non-refundable deposit due upon confirmation:</strong> {{ contract_first_deposit }}</li>
    <li><strong>Second deposit due by:</strong> {{ contract_second_deposit_due_at }} - {{ contract_second_deposit }}</li>
    <li><strong>Balance due by:</strong> {{ contract_balance_due_at }} - {{ contract_balance }}</li>
    <li>VAT is not applicable on this operation, pursuant to article 1, paragraphs from 54 to 89, Italian Law n. 190/2014 and amended to Law n. 208/2015 and Law n. 145/2018. 4% additional contribution INPS is included in above rates.</li>
</ul>

<p>
    The contract will be valid after receiving a countersigned copy in PDF format within 10 days and after the
    payment of the first deposit has been done by the Client.
</p>

<p>
    If the Planner does not receive payments on time, the Planner might decide to cancel the service booked
    for this event. Payment methods can be agreed with the Planner (Wise, cash or bank transfer).
</p>

<h2>4. Cancellation</h2>
<p><strong>By Client.</strong> The Client may cancel this Contract at any time.</p>
<p>
    If the Client cancels up to 366 days prior to the Wedding Day, the Client will be entitled to a full refund
    of the second deposit, if already paid, except for the first non-refundable deposit.
</p>
<p>
    If the Client cancels between 180 and up to 120 days prior to the Wedding Day, the Client will be entitled
    to a 50% refund of the second deposit, if already paid.
</p>
<p>
    If the Client cancels less than 120 days prior to the Wedding Day, the Client will not be entitled to any refund.
    For cancellations received during the last 60 days prior the wedding, total fee for the service is due.
</p>

<p><strong>Force Majeure.</strong> In case of postponements due to Covid-19 or force majeure, all deposits paid can be used as a credit to reschedule the event until {{ force_majeure_credit_until }}.</p>

<p>
    <strong>By Planner.</strong> The Planner may cancel this Contract at any time. If the Planner cancels,
    the Planner must provide a suitable replacement planner, subject to the Client’s approval, or refund
    monies previously paid by the Client, excluding non-refundable deposits.
    In the event the Planner finds a suitable replacement planner, the Planner shall forward monies previously
    paid by the Client to the replacement planner, less any monies the replacement planner agrees have been
    earned by the Planner for services performed until the date of cancellation.
</p>

<p>
    <strong>No feeling.</strong> In the event of completely divergent visions in the management of the wedding or disputes
    during the planning process that will cause an irreparable lack of feeling and confidence, each party will have
    the right to withdraw from the contract at any time, notifying the other in writing.
    In this case, the first non-refundable deposit will not be refunded. If the second deposit has not been paid yet,
    the Client will pay to the Planner 10% of the agreed fee for every supplier booked so far to cover the time spent
    on working on the project. If the second deposit has already been paid, it will not be refunded.
    For cancellations received during the last 60 days prior the wedding, total fee for the service is due.
 </p>

<h2>5. Budget</h2>
<p>
    The Planner specifies minimum budgets required for all the services and suppliers needed for the wedding day.
    Should the Client decide at any point of the planning process to reduce the budget, the Planner may not be able
    to assist anymore with the wedding if she considers it not feasible for Client’s expectations or requests.
    In this case, the Parties can agree a resolution of the contract under the same payment and refund rules stated above.
</p>

<h2>6. Plan B</h2>
<p>
    In case of bad weather, the Planner requires adequate covered or indoor areas for the different moments of the Wedding.
    If the venue does not have enough covered or indoor areas, it will be mandatory for the Client to hire tents or marquees.
    The Planner is not responsible should the Client decide not to rent them. Before the Wedding, the Planner will agree
    with the Client what spaces will be used in case of rain or strong wind. The final decision on where to set up will be
    taken in the morning of the Wedding Day according to the forecasts.
</p>

<h2>7. Client Duties</h2>
<ul>
    <li>The Client agrees to respond to the Planner’s emails in a reasonable time, preferably within 7 days.</li>
    <li>The Client accepts not to get in touch directly with suppliers if not agreed with the Planner.</li>
    <li>The Client accepts to provide staff meals and water for the Planner and any potential assistants during the events.</li>
</ul>

<h2>8. Planner Limitation of Liability</h2>
<p>
    The Planner will endeavor to find top, trusted suppliers and/or service providers. However, the Planner does not guarantee any
    supplier or service provider performance and/or product and will not be responsible for it. For any problem or inconvenience,
    her duty will be to do her best to solve it. The Planner has no exclusivities with certain suppliers. The Client is free to
    bring or find others but the Planner asks to be informed for final approval.
</p>

<p>
    In the event the Client changes the date of the wedding, the Planner will make every effort to accommodate,
    but the Planner’s availability is not guaranteed for any other date than the one stated above.
</p>

<h2>9. Planner Impediment</h2>
<p>
    If the Planner is unable to attend the Wedding Day because of serious illness, mourning or other serious reasons,
    the Planner has the obligation to find a suitable replacement planner for the event. If the Planner is not able to answer
    emails, attend meetings or continue the planning for some time, one of her assistants will temporarily replace her.
</p>

<h2>10. Dispute Resolution and Legal Fees</h2>
<p>
    In the event of a dispute arising out of this Contract that cannot be resolved by mutual agreement, the Parties agree to
    engage in third party mediation. Any dispute will be resolved by Florence Court, Italy.
</p>

<h2>11. Legal and Binding Agreement</h2>
<p>
    This Contract is legal and binding between the Parties as stated above. This Contract may be entered into and is legal
    and binding both in the United States and throughout Europe. The Parties each represent that they have the authority
    to enter into this Contract.
</p>

<h2>12. Governing Law and Jurisdiction</h2>
<p>The Parties agree that this Contract shall be governed by the Country in which the wedding will occur, Italy.</p>

<h2>13. Entire Agreement</h2>
<p>
    The Parties acknowledge and agree that this Contract represents the entire agreement between the Parties.
    In the event that the Parties desire to change, add, or otherwise modify any terms, they shall do so in writing and signed by both parties.
</p>

<h2>Signatures</h2>
<p>The Parties agree to the terms and conditions set forth above as demonstrated by their signatures as follows:</p>

<p><strong>Client</strong></p>
<p>Signed: _________________________________</p>
<p>Name: {{ couple_name }}</p>
<p>Date: {{ contract_sent_at }}</p>

<p><strong>Planner</strong></p>
<p>Signed: _________________________________</p>
<p>Name: Irene Cencetti</p>
<p>Date: {{ contract_sent_at }}</p>

<p>
    As ruled in articles 1341 and 1342 of the Italian Civil Code, the Client approves separately above clauses n°
    2.2, 3, 4, 5, 6, 7, 9, 10. This contract will not be valid without the approval in writing with Client’s signature below.
</p>

<p><strong>Client</strong></p>
<p>Signed: _________________________________</p>
<p>Name: {{ couple_name }}</p>
<p>Date: {{ contract_sent_at }}</p>

<h2>Privacy</h2>
<p>
    The Planner hereby informs you that the European Regulation 2016/679 (G.D.P.R.) provides for the protection of persons
    with respect to the processing of personal data and the free circulation of data.
    Pursuant to the aforementioned legislation, the processing of your personal data will be based on principles of correctness,
    lawfulness, transparency and protection of your privacy and your rights.
</p>

<p>
    The Client consents to the processing of the provided personal data as ruled by Italian Legislative Decree 196, June 30th 2003.
    This privacy code guarantees a high-level protection on processing of your personal data.
</p>

<ol>
    <li><strong>Purposes and methods of data processing.</strong> Your personal data will be processed exclusively for purposes connected to the fulfilment of the obligations inherent in the existing contract, including accounting, receipts and payments, and compliance with civil, fiscal, regulatory and EU obligations.</li>
    <li><strong>Nature of data collection and consequences of failure to provide data.</strong> The provision of personal data is mandatory in order to fulfil the obligations arising from the contract and legal requirements.</li>
    <li><strong>Communication and dissemination of data.</strong> Your personal data may be communicated to legal, administrative, tax and accounting professionals, banks, factoring or debt collection companies, collaborators and specially authorized employees when needed for the contract execution. The data collected will not be disseminated in any case.</li>
    <li><strong>Data retention.</strong> The Planner will keep your data only for the time necessary to provide the requested services or longer if required by laws, regulations, disputes or judicial checks. When no longer necessary, the data will be destroyed securely or made permanently unidentifiable.</li>
</ol>

<p><strong>Client</strong></p>
<p>Signed: _________________________________</p>
<p>Name: {{ couple_name }}</p>
<p>Date: {{ contract_sent_at }}</p>

<h2>Photo and Video Release Form</h2>
<p>
    I hereby grant permission to Fairytale Italy Weddings to use my professional wedding photographs and videos publicly
    to promote its business. Fairytale Italy Weddings will mainly use pictures and videos of table set-ups, ceremony set-ups,
    details and just some of the couple, bridal party and guests.
</p>
<p>
    I understand that the images may be used in print publications, brochures, online publications, presentations, websites,
    and social media. I also understand that no royalty, fee or other compensation shall become payable to me by reason of such use.
    Images will be stored in a secure location and only authorized staff will have access to them. They will be kept as long as
    they are relevant and after that time destroyed or archived.
</p>

<p>Signature: _________________________________</p>
<p>Name: {{ couple_name }}</p>
<p>Date: {{ contract_sent_at }}</p>
HTML,
            ],
        );
    }
}
