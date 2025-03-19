    <!-- Create Modal -->
    <div class="modal fade" id="createAbsenceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إنشاء طلب غياب جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('absence-requests.store') }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    <div class="modal-body">
                        <!-- اختيار الموظف (يظهر فقط للمدراء) -->
                        @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']))
                        <div class="mb-3">
                            <label for="user_id" class="form-label">الموظف</label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <option value="">اختر الموظف...</option>
                                <option value="{{ Auth::id() }}">أنا ({{ Auth::user()->name }})</option>
                                @foreach($users->where('id', '!=', Auth::id()) as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">
                                يرجى اختيار الموظف
                            </div>
                        </div>
                        @endif

                        <!-- تاريخ الغياب -->
                        <div class="mb-3">
                            <label for="absence_date" class="form-label">تاريخ الغياب</label>
                            <input type="date" class="form-control" id="absence_date" name="absence_date"
                                required>
                            <div class="invalid-feedback">
                                يرجى اختيار تاريخ الغياب
                            </div>
                        </div>

                        <!-- سبب الغياب -->
                        <div class="mb-3">
                            <label for="reason" class="form-label">سبب الغياب</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                            <div class="invalid-feedback">
                                يرجى كتابة سبب الغياب
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">إنشاء الطلب</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editAbsenceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editAbsenceForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Absence Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_absence_date" class="form-label">Absence Date</label>
                            <input type="date"
                                class="form-control"
                                id="edit_absence_date"
                                name="absence_date"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_reason" class="form-label">Reason</label>
                            <textarea class="form-control"
                                id="edit_reason"
                                name="reason"
                                required
                                maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Respond Modal -->
    <div class="modal fade" id="respondModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="respondForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="responseTitle">الرد على الطلب</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="response_type" id="response_type">

                        <div class="mb-3">
                            <label class="form-label">الحالة</label>
                            <select class="form-select" id="response_status" name="status" required>
                                <option value="approved">موافق</option>
                                <option value="rejected">مرفوض</option>
                            </select>
                        </div>

                        <div class="mb-3" id="response_reason_container" style="display: none;">
                            <label class="form-label">سبب الرفض</label>
                            <textarea class="form-control"
                                id="response_reason"
                                name="rejection_reason"
                                maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ الرد</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modify Response Modal -->
    <div class="modal fade" id="modifyResponseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="modifyResponseForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="response_type" id="modify_response_type">

                    <div class="modal-header">
                        <h5 class="modal-title">تعديل الرد</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="modify_status" class="form-label">الحالة</label>
                            <select class="form-select" id="modify_status" name="status" required>
                                <option value="approved">موافق</option>
                                <option value="rejected">مرفوض</option>
                            </select>
                        </div>

                        <div class="mb-3" id="modify_reason_container" style="display: none;">
                            <label for="modify_reason" class="form-label">سبب الرفض</label>
                            <textarea class="form-control"
                                id="modify_reason"
                                name="rejection_reason"
                                maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
