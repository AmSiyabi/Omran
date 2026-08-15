<?php

/**
 * سلاسل المصادقة — عربية أولاً.
 */
return [

    // Laravel defaults
    'failed' => 'بيانات الدخول غير صحيحة.',
    'password' => 'كلمة المرور غير صحيحة.',
    'throttle' => 'محاولات دخول كثيرة. حاول مرة أخرى بعد :seconds ثانية.',

    // تسجيل الدخول
    'login_title' => 'تسجيل الدخول',
    'login' => 'دخول',
    'logout' => 'تسجيل الخروج',
    'email' => 'البريد الإلكتروني',
    'password_label' => 'كلمة المرور',
    'remember_me' => 'تذكرني على هذا الجهاز',
    'forgot_password' => 'نسيت كلمة المرور؟',

    // التحقق بخطوتين
    'two_factor_title' => 'التحقق بخطوتين',
    'two_factor_hint' => 'أدخل رمز التحقق من تطبيق المصادقة على هاتفك.',
    'two_factor_code' => 'رمز التحقق',
    'recovery_code' => 'رمز الاسترداد',
    'recovery_code_hint' => 'أدخل أحد رموز الاسترداد التي حفظتها عند تفعيل التحقق بخطوتين.',
    'use_recovery_code' => 'استخدام رمز استرداد بدلاً من ذلك',
    'use_totp_code' => 'استخدام رمز التطبيق بدلاً من ذلك',
    'verify' => 'تحقق',
    'two_factor_required_title' => 'تفعيل التحقق بخطوتين مطلوب',
    'two_factor_required_hint' => 'حسابك من نوع مالك أو مدير، ويتطلب تفعيل التحقق بخطوتين قبل استخدام لوحة التحكم.',
    'two_factor_enable' => 'تفعيل التحقق بخطوتين',
    'two_factor_disable' => 'إيقاف التحقق بخطوتين',
    'two_factor_enabled' => 'تم تفعيل التحقق بخطوتين',
    'two_factor_confirmed' => 'تم تأكيد التحقق بخطوتين بنجاح',
    'two_factor_scan_qr' => 'امسح رمز QR بتطبيق المصادقة (مثل Google Authenticator أو Authy)، ثم أدخل الرمز الظاهر للتأكيد.',
    'two_factor_hint_setup' => 'التحقق بخطوتين يضيف طبقة حماية لحسابك: عند تسجيل الدخول ستحتاج رمزاً من تطبيق المصادقة على هاتفك إضافة إلى كلمة المرور.',
    'two_factor_manage_hint' => 'أدخل كلمة المرور لتنفيذ أي من الإجراءات أدناه.',
    'two_factor_status_hint' => 'حالة التحقق بخطوتين لحسابك.',
    'two_factor_secret_key' => 'أو أدخل المفتاح يدوياً',
    'recovery_codes_title' => 'رموز الاسترداد',
    'recovery_codes_hint' => 'احفظ هذه الرموز في مكان آمن — تظهر مرة واحدة فقط، وتُستخدم للدخول إذا فقدت هاتفك.',
    'recovery_codes_regenerate' => 'توليد رموز جديدة',

    // استعادة كلمة المرور
    'forgot_password_title' => 'استعادة كلمة المرور',
    'forgot_password_hint' => 'أدخل بريدك الإلكتروني وسنرسل لك رابط إعادة تعيين كلمة المرور.',
    'send_reset_link' => 'إرسال رابط الاستعادة',
    'back_to_login' => 'العودة لتسجيل الدخول',
    'reset_password_title' => 'إعادة تعيين كلمة المرور',
    'reset_password' => 'إعادة التعيين',
    'new_password' => 'كلمة المرور الجديدة',
    'confirm_new_password' => 'تأكيد كلمة المرور الجديدة',
    'password_policy_hint' => '12 حرفاً على الأقل',

    // تأكيد كلمة المرور
    'confirm_password_title' => 'تأكيد كلمة المرور',
    'confirm_password_hint' => 'هذه منطقة حساسة — أكد كلمة المرور للمتابعة.',

    // تأكيد البريد
    'verify_email_title' => 'تأكيد البريد الإلكتروني',
    'verify_email_hint' => 'أرسلنا رابط تأكيد إلى بريدك الإلكتروني. اضغط الرابط لتفعيل حسابك.',
    'verification_link_sent' => 'تم إرسال رابط تأكيد جديد إلى بريدك الإلكتروني.',
    'resend_verification' => 'إعادة إرسال رابط التأكيد',

    // الجلسات والأجهزة
    'sessions_title' => 'الجلسات النشطة',
    'sessions_hint' => 'الأجهزة المسجلة الدخول إلى حسابك حالياً.',
    'current_session' => 'الجلسة الحالية',
    'last_active' => 'آخر نشاط',
    'revoke_session' => 'إنهاء الجلسة',
    'session_revoked' => 'تم إنهاء الجلسة',
    'unknown_device' => 'جهاز غير معروف',
    'new_device_subject' => 'تسجيل دخول من جهاز جديد — مركز عمران',
    'new_device_line1' => 'سجّل أحدهم الدخول إلى حسابك من جهاز جديد.',
    'new_device_ip' => 'عنوان IP: :ip',
    'new_device_time' => 'الوقت: :time',
    'new_device_warning' => 'إذا لم يكن هذا أنت، غيّر كلمة المرور فوراً.',

    // الحساب
    'account_inactive' => 'هذا الحساب معطل. تواصل مع مالك المركز.',
    'security_title' => 'الأمان',

];
