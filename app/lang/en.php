<?php
return [
    // Navigation
    'nav.group_comm' => 'Communication', 'nav.group_hospital' => 'Hospital',
    'nav.dashboard' => 'Dashboard', 'nav.patients' => 'Patients', 'nav.appointments' => 'Appointments',
    'nav.my_appointments' => 'My appointments', 'nav.consultations' => 'Consultations', 'nav.queue' => 'Queue', 'nav.anc' => 'ANC',
    'nav.reschedule' => 'Reschedule requests', 'nav.sms_templates' => 'SMS templates', 'nav.reminder_rules' => 'Reminder rules',
    'nav.sms_logs' => 'SMS delivery', 'nav.staff' => 'Staff & roles', 'nav.departments' => 'Departments',
    'nav.reports' => 'Reports', 'nav.billing' => 'Billing & plan', 'nav.audit_logs' => 'Audit log', 'nav.settings' => 'Settings',
    'nav.hospitals' => 'Hospitals', 'nav.payments' => 'Payments', 'nav.enterprise_requests' => 'Enterprise requests', 'nav.plans' => 'Plans',
    'nav.my_bookings' => 'My appointments', 'nav.book' => 'Book appointment', 'nav.history' => 'History', 'nav.notifications' => 'Notifications',
    'nav.my_account' => 'My account', 'nav.sign_out' => 'Sign out',

    // Common
    'common.save' => 'Save', 'common.cancel' => 'Cancel', 'common.edit' => 'Edit', 'common.delete' => 'Delete',
    'common.back' => 'Back', 'common.search' => 'Search', 'common.sign_in' => 'Sign in', 'common.sign_up' => 'Sign up',
    'common.email' => 'Email', 'common.phone' => 'Phone', 'common.password' => 'Password', 'common.confirm_password' => 'Confirm password',

    // Auth
    'auth.sign_in_title' => 'Sign in', 'auth.identifier' => 'Email or phone', 'auth.forgot' => 'Forgot your password?',
    'auth.new_hospital' => 'New hospital?', 'auth.start_trial' => 'Start a free trial',
    'auth.are_you_patient' => 'Are you a patient?', 'auth.are_you_doctor' => 'Are you a doctor?', 'auth.register_here' => 'Register here',
    'auth.already_have_account' => 'Already have an account?',

    'patreg.title' => 'Register as a patient', 'patreg.subtitle' => 'Create your account to request appointments from wherever you are — no hospital visit needed.',
    'patreg.hospital' => 'Hospital', 'patreg.choose_hospital' => '— Choose your hospital —', 'patreg.first_name' => 'First name',
    'patreg.last_name' => 'Last name', 'patreg.email_optional' => 'Email (optional)', 'patreg.create_account' => 'Create account',

    'docreg.title' => 'Register as a doctor', 'docreg.subtitle' => 'Your account will be approved by your hospital administrator before it becomes active.',
    'docreg.hospital' => 'Hospital you work at', 'docreg.full_name' => 'Full name', 'docreg.specialty' => 'Specialty',
    'docreg.send_request' => 'Send request', 'docreg.pending' => 'Your request was received. The hospital administrator must approve it before you can sign in. You\'ll be notified once approved.',

    // Landing
    'landing.hero_title' => 'Never miss an important patient appointment.',
    'landing.hero_lead' => 'This system schedules appointments and automatically reminds every patient by SMS, email or WhatsApp — before they even think to ask.',
    'landing.get_started' => 'Get started free', 'landing.request_demo' => 'Request a demo', 'landing.trial_note' => 'Free {days}-day trial · No card required',
    'landing.features_title' => 'Everything the front desk needs', 'landing.how_title' => 'How it works',
    'landing.pricing_title' => 'Pricing', 'landing.demo_title' => 'Request a demo',

    // Dashboard
    'dash.patients' => 'Patients', 'dash.today_appts' => "Today's appointments", 'dash.upcoming' => 'Upcoming appointments',
    'dash.missed' => 'Missed (30 days)', 'dash.sms_today' => 'SMS sent today', 'dash.sms_pending' => 'SMS pending',
    'dash.waiting' => 'Currently waiting', 'dash.reschedule_pending' => 'Reschedule requests', 'dash.todays_schedule' => "Today's schedule",

    // Portal
    'portal.hello' => 'Hello, {name}', 'portal.book_title' => 'Request an appointment', 'portal.department' => 'Where do you want to be seen',
    'portal.choose' => '— Choose —', 'portal.what_treat' => 'What do you need', 'portal.date' => 'Date', 'portal.time' => 'Time',
    'portal.available_times' => 'Available times', 'portal.no_doctor_pick' => 'You don\'t choose a doctor — the system will assign one who is free at that time.',
    'portal.send_request' => 'Send request', 'portal.upcoming' => 'Upcoming appointments', 'portal.no_upcoming' => 'No upcoming appointments.',

    // Statuses
    'status.scheduled' => 'Scheduled', 'status.confirmed' => 'Confirmed', 'status.checked_in' => 'Waiting',
    'status.in_consultation' => 'With doctor', 'status.completed' => 'Completed', 'status.cancelled' => 'Cancelled', 'status.missed' => 'Missed',
    'status.pending' => 'Pending', 'status.approved' => 'Approved', 'status.rejected' => 'Rejected',

    // Roles
    'nav.group_finance' => 'Finance', 'nav.finance' => 'Finance dashboard', 'nav.invoices' => 'Invoices', 'nav.expenses' => 'Expenses', 'role.finance' => 'Finance',
    'status.active' => 'Active', 'status.suspended' => 'Suspended', 'status.sending' => 'Sending', 'status.sent' => 'Sent', 'status.delivered' => 'Delivered', 'status.failed' => 'Failed', 'status.planned' => 'Planned', 'status.booked' => 'Booked', 'status.attended' => 'Attended', 'status.low' => 'Low', 'status.moderate' => 'Moderate', 'status.high' => 'High', 'status.closed' => 'Closed', 'status.trial' => 'Trial', 'status.past_due' => 'Expired',
    'portal.request_diff_date' => 'Request a different date', 'portal.preferred_date' => 'Preferred date', 'portal.preferred_time' => 'Preferred time (optional)', 'portal.reason' => 'Reason', 'portal.at' => 'at', 'portal.hello_name' => 'Hello, {name}',
    'nav.home' => 'Home', 'landing.choose_login' => 'How are you signing in?', 'landing.staff_login' => 'Hospital staff', 'landing.staff_login_sub' => 'Admin, doctor, nurse or reception', 'landing.patient_login' => 'Patient', 'landing.patient_login_sub' => 'View or request your appointments', 'landing.continue' => 'Continue', 'landing.staff_signin_title' => 'Staff sign in', 'landing.patient_signin_title' => 'Patient sign in', 'landing.features_lead' => 'One system for scheduling, reminders and records — built for how clinics actually run their day.', 'landing.pricing_lead' => 'Simple monthly plans. Pay monthly or yearly — cancel anytime.', 'landing.how_lead' => 'From booking to the reminder landing in a patient\'s pocket — four steps, nothing manual.',
    'status.unpaid' => 'Unpaid', 'status.partial' => 'Partially paid', 'status.paid' => 'Paid',
    'role.super_admin' => 'Super admin', 'role.hospital_admin' => 'Hospital admin', 'role.doctor' => 'Doctor',
    'role.receptionist' => 'Receptionist', 'role.nurse' => 'Nurse', 'role.patient' => 'Patient',
];
