<?php
/**
 * نظام طلبات الاستئذان
 * ====================
 *
 * نظام متكامل يتيح للموظفين تقديم طلبات الخروج المؤقت أثناء ساعات العمل. يتيح النظام للمديرين
 * والموارد البشرية مراجعة الطلبات والموافقة عليها، مع تتبع إحصائيات الاستخدام وحالات العودة.
 *
 * الميزات الرئيسية
 * ---------------
 * - تقديم طلبات استئذان مع تحديد وقت المغادرة ووقت العودة
 * - نظام موافقات متعدد المستويات (المدير والموارد البشرية)
 * - تتبع دقائق الاستئذان المستخدمة والمتبقية (حد شهري 180 دقيقة)
 * - تسجيل عودة الموظف وتتبع الالتزام بالوقت
 * - إشعارات لجميع الأطراف عند تغيير حالة الطلبات
 * - لوحة إحصائيات لمراقبة استخدام الاستئذان
 *
 * المخطط العام للنظام
 * -----------------
 *
 * الأدوار والصلاحيات:
 *
 * - موظف: إنشاء طلبات، تعديل طلباته الخاصة، حذف طلباته المعلقة، تسجيل العودة
 * - قائد فريق: الرد على طلبات أعضاء الفريق، مراجعة إحصائيات الفريق
 * - مدير قسم: الرد على طلبات الموظفين وقادة الفرق، مراجعة إحصائيات القسم
 * - موارد بشرية: الرد على جميع الطلبات، مراجعة جميع الإحصائيات
 *
 * حالات الطلب:
 *
 * - pending: الطلب معلق في انتظار الموافقة
 * - approved: تمت الموافقة على الطلب من المدير والموارد البشرية
 * - rejected: تم رفض الطلب من المدير أو الموارد البشرية
 *
 * حالات العودة:
 *
 * - غير محدد: 0 أو null - لم يتم تسجيل العودة بعد
 * - عاد في الوقت المحدد: 1 أو true - الموظف عاد في الوقت المحدد
 * - لم يعد في الوقت المحدد: 2 - الموظف لم يعد في الوقت المحدد (مخالفة)
 */

/**
 * مكونات النظام
 * ============
 *
 * @file PermissionRequest.php
 * @description النموذج الرئيسي لطلب الاستئذان
 *
 * يتضمن:
 * - معلومات الطلب (وقت المغادرة، وقت العودة، السبب)
 * - حالة الطلب (معلق، مقبول، مرفوض)
 * - حالة موافقة المدير والموارد البشرية
 * - حالة العودة (عاد في الوقت المحدد أم لا)
 * - الدقائق المستخدمة والمتبقية
 * - العلاقات مع المستخدم والمخالفات
 *
 * المسؤوليات:
 * - التحقق من صلاحيات الأدوار المختلفة
 * - حساب الدقائق المستخدمة والمتبقية
 * - تحديث حالة الطلب النهائية بناءً على ردود المدير والموارد البشرية
 * - تتبع حالة العودة
 *
 * الدوال الرئيسية:
 *
 * - canRespond(User $user): bool - التحقق إذا كان المستخدم يمكنه الرد على الطلب
 * - canCreate(User $user): bool - التحقق إذا كان المستخدم يمكنه إنشاء طلب
 * - canUpdate(User $user): bool - التحقق إذا كان المستخدم يمكنه تعديل الطلب
 * - canDelete(User $user): bool - التحقق إذا كان المستخدم يمكنه حذف الطلب
 * - canModifyResponse(User $user): bool - التحقق إذا كان المستخدم يمكنه تعديل الرد
 * - updateManagerStatus(string $status, ?string $rejectionReason): void - تحديث حالة رد المدير
 * - updateHrStatus(string $status, ?string $rejectionReason): void - تحديث حالة رد الموارد البشرية
 * - updateFinalStatus(): void - تحديث الحالة النهائية للطلب
 * - calculateMinutesUsed(): int - حساب الدقائق المستخدمة
 * - calculateRemainingMinutes(): int - حساب الدقائق المتبقية
 * - calculateActualMinutesUsed(): int - حساب الدقائق الفعلية المستخدمة
 * - updateActualMinutesUsed(): void - تحديث الدقائق الفعلية المستخدمة
 * - canMarkAsReturned(User $user): bool - التحقق إذا كان المستخدم يمكنه تسجيل العودة
 * - canResetReturnStatus(User $user): bool - التحقق إذا كان المستخدم يمكنه إعادة تعيين حالة العودة
 * - isReturnTimePassed(): bool - التحقق إذا كان وقت العودة قد انقضى
 * - shouldShowCountdown(): bool - التحقق إذا كان يجب عرض العد التنازلي
 * - canRespondAsManager(User $user): bool - التحقق إذا كان المستخدم يمكنه الرد كمدير
 * - canRespondAsHR(User $user): bool - التحقق إذا كان المستخدم يمكنه الرد كموارد بشرية
 *
 * @file PermissionRequestService.php
 * @description طبقة منطق الأعمال التي تدير العمليات على طلبات الاستئذان
 *
 * المسؤوليات:
 * - التحقق من صحة طلبات الاستئذان
 * - إنشاء وتحديث وحذف الطلبات
 * - تحديث حالات الطلبات (الموافقة/الرفض)
 * - حساب الدقائق المستخدمة والمتبقية
 * - معالجة حالات العودة
 * - التعامل مع المخالفات
 *
 * الدوال الرئيسية:
 *
 * - getAllRequests($filters): LengthAwarePaginator - الحصول على قائمة الطلبات مع الفلترة
 * - createRequest(array $data): array - إنشاء طلب جديد
 * - createRequestForUser(int $userId, array $data): array - إنشاء طلب لمستخدم آخر
 * - updateRequest(PermissionRequest $request, array $data): array - تحديث طلب موجود
 * - updateStatus(PermissionRequest $request, array $data): array - تحديث حالة الطلب
 * - resetStatus(PermissionRequest $request, string $responseType) - إعادة تعيين حالة الطلب
 * - modifyResponse(PermissionRequest $request, array $data): array - تعديل الرد على الطلب
 * - updateReturnStatus(PermissionRequest $request, int $returnStatus): array - تحديث حالة العودة
 * - getRemainingMinutes(int $userId): int - حساب الدقائق المتبقية للمستخدم
 * - validateTimeRequest(int $userId, string $departureTime, string $returnTime, ?int $excludeId): array - التحقق من صحة أوقات الطلب
 * - canRespond($user = null) - التحقق إذا كان المستخدم يمكنه الرد
 * - deleteRequest(PermissionRequest $request) - حذف طلب
 * - getUserRequests(int $userId): LengthAwarePaginator - الحصول على طلبات المستخدم
 * - getAllowedUsers($user) - الحصول على قائمة المستخدمين المسموح بهم
 *
 * @file PermissionRequestController.php
 * @description وحدة التحكم التي تربط بين الواجهة والخدمات
 *
 * الدوال الرئيسية:
 *
 * - index(Request $request) - عرض قائمة الطلبات وإحصائياتها
 * - store(Request $request) - إنشاء طلب جديد
 * - resetStatus(PermissionRequest $permissionRequest) - إعادة تعيين حالة الطلب
 * - modifyResponse(Request $request, PermissionRequest $permissionRequest) - تعديل الرد على الطلب
 * - update(Request $request, PermissionRequest $permissionRequest) - تحديث طلب موجود
 * - destroy(PermissionRequest $permissionRequest) - حذف طلب
 * - updateStatus(Request $request, PermissionRequest $permissionRequest) - تحديث حالة الموافقة على الطلب
 * - updateReturnStatus(Request $request, PermissionRequest $permissionRequest) - تحديث حالة عودة الموظف
 * - updateHrStatus(Request $request, PermissionRequest $permissionRequest) - تحديث حالة موافقة الموارد البشرية
 * - modifyHrStatus(Request $request, PermissionRequest $permissionRequest) - تعديل حالة موافقة الموارد البشرية
 * - resetHrStatus(Request $request, PermissionRequest $permissionRequest) - إعادة تعيين حالة موافقة الموارد البشرية
 * - updateManagerStatus(Request $request, PermissionRequest $permissionRequest) - تحديث حالة موافقة المدير
 * - resetManagerStatus(PermissionRequest $permissionRequest) - إعادة تعيين حالة موافقة المدير
 * - modifyManagerStatus(Request $request, PermissionRequest $permissionRequest) - تعديل حالة موافقة المدير
 */

/**
 * واجهة المستخدم (Frontend)
 * ========================
 *
 * @file /public/js/permission-requests/common.js
 * @description وظائف مشتركة للنظام
 * - دوال مساعدة للتعامل مع التاريخ والوقت
 * - دوال للعرض وإخفاء العناصر في الواجهة
 * - دوال مساعدة للتعامل مع الطلبات
 *
 * @file /public/js/permission-requests/table.js
 * @description عرض جدول الطلبات وإدارة الفلترة والفرز
 * - تهيئة جدول الطلبات
 * - معالجة الفلترة والفرز
 * - معالجة عمليات التحديث والحذف
 * - عرض حالات الطلبات
 *
 * @file /public/js/permission-requests/modals.js
 * @description نوافذ التفاعل (إنشاء/تعديل الطلبات، تحديث الحالة)
 * - نوافذ إنشاء طلب جديد
 * - نوافذ تعديل طلب موجود
 * - نوافذ تغيير حالة الطلب
 * - نوافذ تسجيل العودة
 *
 * @file /public/js/permission-requests/statistics.js
 * @description عرض إحصائيات الاستئذان
 * - عرض الدقائق المستخدمة والمتبقية
 * - مخططات بيانية لاستخدام الاستئذان
 * - إحصائيات مقارنة بين الموظفين
 * - تقارير استخدام الاستئذان
 *
 * @file /public/js/permission-requests/countdown.js
 * @description عداد تنازلي لوقت العودة المتوقع
 * - عرض الوقت المتبقي للعودة
 * - تنبيهات عند اقتراب موعد العودة
 * - معالجة تسجيل العودة
 *
 * @file /public/js/permission-requests/department-chart.js
 * @description مخططات بيانية لاستخدام الاستئذان حسب الأقسام
 * - مخططات قطاعية للاستخدام حسب القسم
 * - مخططات خطية للاستخدام عبر الزمن
 * - مقارنات بين الأقسام
 *
 * @file /resources/views/permission-requests/index.blade.php
 * @description الصفحة الرئيسية لنظام طلبات الاستئذان
 * - عرض جدول الطلبات
 * - أزرار إنشاء وتعديل الطلبات
 * - عرض الإحصائيات
 */

/**
 * سير العمل
 * ========
 *
 * 1. إنشاء طلب استئذان:
 *    - يقدم الموظف طلب استئذان مع تحديد وقت المغادرة والعودة والسبب
 *    - يتم التحقق من عدم تجاوز الحد الأقصى للدقائق (180 دقيقة شهرياً)
 *    - يتم التحقق من عدم وجود تعارض مع طلبات أخرى
 *
 * 2. عملية الموافقة:
 *    - يراجع المدير الطلب ويوافق عليه أو يرفضه
 *    - تراجع الموارد البشرية الطلب وتوافق عليه أو ترفضه
 *    - يتم تحديث حالة الطلب النهائية بناءً على الردود
 *
 * 3. تنفيذ الاستئذان:
 *    - يتم عرض عداد تنازلي للموظف عند اقتراب وقت العودة
 *    - يسجل الموظف عودته عند الانتهاء من الاستئذان
 *    - يتم احتساب الدقائق الفعلية المستخدمة
 *
 * 4. المتابعة والتقارير:
 *    - يمكن للمديرين والموارد البشرية مراقبة إحصائيات الاستئذان
 *    - يتم تسجيل المخالفات إذا لم يعد الموظف في الوقت المحدد
 */

/**
 * ملاحظات تقنية
 * ===========
 *
 * - يستخدم النظام Carbon لمعالجة التواريخ والأوقات
 * - يدعم النظام تتبع التغييرات باستخدام Laravel Auditing
 * - تم تنفيذ نظام الإشعارات لإبلاغ جميع الأطراف بتغييرات الحالة
 * - يتوافق النظام مع نظام الورديات (shifts) للموظفين
 *
 * الواجهة البرمجية (API):
 *
 * - GET /permission-requests - عرض قائمة الطلبات
 * - POST /permission-requests - إنشاء طلب جديد
 * - PUT/PATCH /permission-requests/{id} - تعديل طلب
 * - DELETE /permission-requests/{id} - حذف طلب
 * - POST /permission-requests/{id}/update-status - تحديث حالة الطلب
 * - POST /permission-requests/{id}/return-status - تحديث حالة العودة
 * - POST /permission-requests/{id}/reset-status - إعادة تعيين حالة الطلب
 */

/**
 * استخدام النظام
 * ============
 *
 * للموظفين:
 * - تقديم طلبات استئذان وتتبع حالتها
 * - تسجيل العودة عند انتهاء الاستئذان
 * - مراقبة الدقائق المستخدمة والمتبقية
 *
 * للمديرين:
 * - مراجعة طلبات فريقهم والموافقة عليها
 * - متابعة إحصائيات استخدام الاستئذان
 * - تسجيل عدم العودة للموظفين عند الحاجة
 *
 * للموارد البشرية:
 * - مراجعة جميع الطلبات والموافقة عليها
 * - متابعة إحصائيات الشركة
 * - تسجيل وإدارة المخالفات
 */
