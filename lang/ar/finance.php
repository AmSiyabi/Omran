<?php

declare(strict_types=1);

/**
 * سلاسل النظام المالي — القيد المزدوج لا يظهر للمستخدم أبداً (spec §8.3):
 * المالك يسجل أحداثاً تجارية والنظام يولّد القيود.
 */
return [

    'finance' => 'المالية',
    'journal' => 'القيود المحاسبية',
    'recent_entries' => 'أحدث الحركات',
    'no_entries' => 'لا توجد حركات مالية بعد',
    'entry_number' => 'رقم القيد',
    'view_lines' => 'تفاصيل القيد',
    'amount' => 'المبلغ (ر.ع.)',
    'date' => 'التاريخ',
    'description' => 'الوصف',
    'account' => 'الحساب',
    'debit' => 'مدين',
    'credit' => 'دائن',
    'receipt_photo' => 'صورة الإيصال',
    'receipt_hint' => 'اختياري — صورة أو PDF حتى 10 ميجابايت',
    'attachments' => 'المرفقات',
    'view_receipt' => 'عرض الإيصال',

    // الإجراءات الستة
    'record_revenue' => 'تسجيل إيراد',
    'record_payment' => 'تسجيل تحصيل',
    'record_direct_cost' => 'مصروف دورة',
    'record_opex' => 'مصروف تشغيلي',
    'record_payout' => 'مسحوبات شريك',
    'record_contribution' => 'مساهمة رأس مال',

    'action_hint_revenue' => 'فاتورة صدرت أو دخل استحق لدفعة — يُسجل ذمة على العميل.',
    'action_hint_payment' => 'مبلغ وصل فعلاً إلى الصندوق أو البنك من ذمم سابقة.',
    'action_hint_direct_cost' => 'تكلفة تخص دفعة بعينها: إعلانات، قاعة، مواد، سفر…',
    'action_hint_opex' => 'مصروف على مستوى المركز لا يخص دورة: اشتراكات، استضافة…',
    'action_hint_payout' => 'تحويل من الحساب الجاري للشريك إلى جيبه.',
    'action_hint_contribution' => 'شريك يضخ مالاً في المركز — يزيد رأس ماله.',

    'cohort' => 'الدفعة',
    'no_cohort' => 'بدون دفعة',
    'revenue_account' => 'نوع الإيراد',
    'cost_account' => 'بند التكلفة',
    'expense_account' => 'بند المصروف',
    'cash_account_into' => 'إلى حساب',
    'cash_account_from' => 'من حساب',
    'partner' => 'الشريك',

    'recorded_successfully' => 'سُجلت الحركة: :description بمبلغ :amount',
    'entry_reversed' => 'تم عكس القيد :number',
    'reverse_entry' => 'عكس القيد',
    'reverse_reason' => 'سبب العكس',
    'reverse_entry_title' => 'عكس القيد :number',
    'reverse_entry_warning' => 'سيُنشأ قيد عكسي مضاد ويبقى الأصل ظاهراً بحالة «معكوس». لا يُحذف شيء أبداً.',

    // الحالات
    'entry_status.posted' => 'مرحَّل',
    'entry_status.reversed' => 'معكوس',
    'account_type.asset' => 'أصول',
    'account_type.liability' => 'التزامات',
    'account_type.equity' => 'حقوق ملكية',
    'account_type.revenue' => 'إيرادات',
    'account_type.direct_cost' => 'تكاليف مباشرة',
    'account_type.operating_expense' => 'مصروفات تشغيلية',
    'document_type.receipt' => 'إيصال',
    'document_type.invoice' => 'فاتورة',
    'document_type.contract' => 'عقد',
    'document_type.quote' => 'عرض سعر',
    'document_type.other' => 'أخرى',

    // الإضافة السريعة
    'quick_add' => 'مصروف سريع',
    'quick_add_title' => 'تسجيل مصروف',
    'quick_add_success' => 'تم تسجيل مصروف بقيمة :amount:cohort',
    'quick_add_for_cohort' => ' لدفعة :code',

    // التصفيات
    'settlements' => 'التصفيات',
    'settlement' => 'التصفية',
    'new_settlement' => 'تصفية جديدة',
    'settle_cohort' => 'احسب التصفية',
    'ready_to_settle' => 'دفعات جاهزة للتصفية',
    'no_ready_cohorts' => 'لا توجد دفعات منفذة بانتظار التصفية',
    'no_settlements' => 'لا توجد تصفيات بعد',
    'settlement_number' => 'رقم التصفية',
    'settlement_status.draft' => 'مسودة',
    'settlement_status.confirmed' => 'مؤكدة',
    'settlement_status.posted' => 'مرحَّلة',
    'settlement_status.reversed' => 'معكوسة',
    'settlement_type.cohort' => 'تصفية دفعة',
    'settlement_type.monthly' => 'تصفية شهرية',

    'gross_revenue' => 'إجمالي الإيرادات',
    'direct_costs' => 'التكاليف المباشرة',
    'net_distributable' => 'الصافي القابل للتوزيع',
    'deliverer_share' => 'حصة المنفذين',
    'center_share' => 'حصة المركز',
    'external_fee' => 'أجر المدرب الخارجي',
    'per_policy' => 'حسب سياسة «:name»',
    'deliverer_breakdown' => 'توزيع حصة المنفذين',
    'center_breakdown' => 'توزيع حصة المركز على الشريكين',
    'partner_total' => 'إجمالي استحقاق كل شريك',

    'recompute' => 'إعادة الحساب',
    'confirm_settlement' => 'تأكيد التصفية وترحيلها',
    'settlement_confirmed' => 'تم ترحيل التصفية :number',
    'settlement_recomputed' => 'أُعيد حساب المسودة',
    'confirm_settlement_title' => 'تأكيد التصفية',
    'confirm_settlement_text' => 'سيُجمَّد الحساب ويُرحَّل القيد ولن يكون قابلاً للتعديل — التصحيح لاحقاً يكون بالعكس فقط.',

    'flag_loss_title' => 'هذه الدفعة خاسرة',
    'flag_loss_text' => 'الصافي سالب: لا حصة للمنفذ، والخسارة تُحمَّل على وعاء المركز مناصفة. أكد قبولك صراحة للمتابعة.',
    'accept_loss' => 'أُقر بقبول تحميل الخسارة على الشريكين',
    'flag_overcommitted_title' => 'الأجر الثابت أعلى من الصافي',
    'flag_overcommitted_text' => 'أجر المدرب المتفق عليه يتجاوز الصافي القابل للتوزيع — التصفية محجوبة وتحتاج تجاوزاً مسبباً من مالك.',
    'override_reason' => 'سبب التجاوز (إلزامي)',

    'reverse_settlement' => 'عكس التصفية',
    'settlement_reversed' => 'تم عكس التصفية',
    'reverse_settlement_title' => 'عكس التصفية :number',
    'reverse_settlement_warning' => 'سيُعكس قيد التصفية وتعود الدفعة إلى حالة «منفذة» لتُصفى من جديد.',
    'snapshot_notice' => 'هذه أرقام مجمّدة لحظة التأكيد — تعديلات السياسات اللاحقة لا تغيّرها.',

];
