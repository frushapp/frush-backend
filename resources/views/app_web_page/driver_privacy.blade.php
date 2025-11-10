@extends('layouts.landing.app')

@section('title', 'Driver Privacy Policy')



@push('css_or_js')
    <style>
        @media print {
            .non-printable {
                display: none;
            }

            .printable {
                display: block;
                font-family: emoji !important;
            }

            body {
                -webkit-print-color-adjust: exact !important;
                /* Chrome, Safari */
                color-adjust: exact !important;
                font-family: emoji !important;
            }
        }
    </style>

    <style type="text/css" media="print">
        @page {
            size: auto;
            /* auto is the initial value */
            margin: 2px;
            /* this affects the margin in the printer settings */
            font-family: emoji !important;
        }
    </style>
@endpush

@section('content')
    <section>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h1>Privacy Policy</h1>

                    <p>This Privacy Policy governs the manner in which <strong>Frush</strong> collects, uses, maintains, and
                        discloses information collected from users (referred to as "Clients") of the Frush website and
                        mobile application ("App"). This policy applies to the App and all products and services offered by
                        Frush.</p>

                    <h2>Personal Identification Information</h2>
                    <p>Frush may collect personal identification information from Clients in various ways, including but not
                        limited to when Clients visit the App, register on the App, place an order, subscribe to the
                        newsletter, respond to a survey, fill out a form, or interact with other activities, services,
                        features, or resources available on the App. Clients may be asked for their name, email address,
                        mailing address, phone number, and other relevant information. Clients have the right to refuse to
                        provide personal identification information, with the understanding that it may prevent them from
                        engaging in certain App-related activities.</p>

                    <h2>Non-personal Identification Information</h2>
                    <p>Frush may collect non-personal identification information about Clients whenever they interact with
                        the App. Non-personal identification information may include the browser name, the type of computer
                        or device, and technical information about Clients' means of connection to the App, such as the
                        operating system and the Internet service providers utilized.</p>

                    <h2>Protection of Information</h2>
                    <p>Frush adopts appropriate data collection, storage, and processing practices and security measures to
                        protect against unauthorized access, alteration, disclosure, or destruction of personal information,
                        username, password, transaction information, and data stored on the App.</p>

                    <h2>Use of Collected Information</h2>
                    <p>Frush may collect and use Clients' personal information for various purposes, including but not
                        limited to:</p>
                    <ul>
                        <li>To personalize the user experience</li>
                        <li>To improve customer service</li>
                        <li>To process transactions</li>
                        <li>To send periodic emails</li>
                        <li>To administer content, promotions, surveys, or other App features</li>
                    </ul>

                    <p>Other optional information may also be requested during the course of registration or usage of the
                        services. Without prejudice to the generality of the above, information collected by us from you may
                        include (but is not limited to) the following:</p>

                    <ul>
                        <li><strong>(a)</strong> Contact data (such as your email address, phone number, and access to your
                            contact book)</li>
                        <li><strong>(b)</strong> Demographic data (such as your date of birth and your pin code)</li>
                        <li><strong>(c)</strong> Information provided by you while initiating and operating the services,
                            including, without limitation:
                            <ul>
                                <li>Search words</li>
                                <li>Location</li>
                                <li>Services being sought</li>
                                <li>All call and chat history</li>
                                <li>Any other information that you voluntarily choose to provide to us (such as information
                                    shared by you with us through emails or letters, your work details, your family details)
                                </li>
                            </ul>
                        </li>
                        <li><strong>(d)</strong> Other information:
                            <ul>
                                <li>We collect contact information like your name and email address, phone number, or
                                    mailing address.</li>
                                <li>We collect demographic information like your age, and language preferences.</li>
                                <li>We collect information you submit or post like feedback and comments about Frush and
                                    services that you submit to us.</li>
                            </ul>
                        </li>
                    </ul>

                    <h2>Sharing Personal Information</h2>
                    <p>Frush does not sell, trade, or rent Clients' personal identification information to others. Frush may
                        share generic aggregated demographic information not linked to any personal identification
                        information regarding Clients with our business partners, trusted affiliates, and advertisers for
                        the purposes outlined above.</p>

                    <h2>Compliance with Legal Obligations</h2>
                    <p>Frush may disclose personal information if required to do so by law or in response to valid requests
                        by public authorities (e.g., a court or a government agency).</p>

                    <h2>Changes to Privacy Policy</h2>
                    <p>Frush has the discretion to update this privacy policy at any time. Clients are encouraged to
                        frequently check this page for any changes to stay informed about how Frush is helping to protect
                        the personal information collected. Clients acknowledge and agree that it is their responsibility to
                        review this privacy policy periodically and become aware of modifications.</p>

                    <h2>Acceptance of Terms</h2>
                    <p>By using Frush, Clients signify their acceptance of this policy. If Clients do not agree to this
                        policy, please do not use the App. Continued use of the App following the posting of changes to this
                        policy will be deemed as the acceptance of those changes.</p>

                    <h2>Contact Us</h2>
                    <p>If you have any questions or concerns regarding this Privacy Policy, please contact us at:
                        <br><strong>Email:</strong> <a href="mailto:tech.admin@frushapp.com">tech.admin@frushapp.com</a>
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
