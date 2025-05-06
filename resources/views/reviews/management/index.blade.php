@extends('layouts.app')

@section('htmldir', 'rtl')

@push('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('css/reviews-management.css') }}" rel="stylesheet">
    <style>
        .select-all-container {
            margin-bottom: 10px;
        }
        .bulk-actions {
            margin-bottom: 15px;
        }
        .card {
            opacity: 0;
            transform: translateY(20px);
        }
        /* 2FA Modal Styles */
        .two-factor-modal .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .two-factor-modal .form-control {
            text-align: center;
            letter-spacing: 0.5em;
            font-size: 1.5em;
        }
    </style>
@endpush

@section('content')
<div class="container">
    <div class="reviews-management-header mb-4">
        <h1 class="display-5 fw-bold text-center">المراجعات المحذوفة</h1>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fa fa-laptop-code me-2"></i>مراجعات الفريق التقني المحذوفة</h5>
                </div>
                <div class="card-body">
                    @if($technicalReviews->isEmpty())
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-2"></i>لا توجد مراجعات محذوفة للفريق التقني
                        </div>
                    @else
                        <form action="{{ route('hr.reviews.technical.bulk-restore') }}" method="POST" id="technicalBulkForm">
                            @csrf
                            <div class="bulk-actions">
                                <button type="submit" formaction="{{ route('hr.reviews.technical.bulk-restore') }}" class="btn btn-success btn-sm">
                                    <i class="fa fa-trash-restore"></i> استعادة المحدد
                                </button>
                                <button type="submit" formaction="{{ route('hr.reviews.technical.bulk-delete') }}" class="btn btn-danger btn-sm"
                                        onclick="return confirm('هل أنت متأكد من حذف المراجعات المحددة؟')">
                                    <i class="fa fa-trash"></i> حذف المحدد نهائيًا
                                </button>
                            </div>

                            <div class="select-all-container">
                                <div class="form-check">
                                    <input class="form-check-input select-all" type="checkbox" id="selectAllTechnical" data-target="technicalCheckbox">
                                    <label class="form-check-label" for="selectAllTechnical">تحديد الكل</label>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>تحديد</th>
                                            <th>#</th>
                                            <th>الموظف</th>
                                            <th>المقيم</th>
                                            <th>تاريخ المراجعة</th>
                                            <th>تاريخ الحذف</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($technicalReviews as $review)
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input technicalCheckbox" type="checkbox" name="reviews[]" value="{{ $review->id }}" id="technical{{ $review->id }}">
                                                    </div>
                                                </td>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $review->user->name }}</td>
                                                <td>{{ $review->reviewer->name }}</td>
                                                <td>{{ $review->created_at->format('Y-m-d') }}</td>
                                                <td>{{ $review->deleted_at->format('Y-m-d') }}</td>
                                                <td>
                                                    <a href="{{ route('hr.reviews.technical.show', $review->id) }}" class="btn btn-sm btn-info" title="عرض">
                                                        <i class="fa fa-eye"></i> عرض
                                                    </a>
                                                    <form action="{{ route('hr.reviews.technical.restore', $review->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" title="استعادة" onclick="return confirm('هل أنت متأكد من استعادة هذه المراجعة؟')">
                                                            <i class="fa fa-trash-restore"></i> استعادة
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('hr.reviews.technical.delete', $review->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="حذف نهائي">
                                                            <i class="fa fa-trash"></i> حذف نهائي
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fa fa-bullhorn me-2"></i>مراجعات فريق التسويق المحذوفة</h5>
                </div>
                <div class="card-body">
                    @if($marketingReviews->isEmpty())
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-2"></i>لا توجد مراجعات محذوفة لفريق التسويق
                        </div>
                    @else
                        <form action="{{ route('hr.reviews.marketing.bulk-restore') }}" method="POST" id="marketingBulkForm">
                            @csrf
                            <div class="bulk-actions">
                                <button type="submit" formaction="{{ route('hr.reviews.marketing.bulk-restore') }}" class="btn btn-success btn-sm">
                                    <i class="fa fa-trash-restore"></i> استعادة المحدد
                                </button>
                                <button type="submit" formaction="{{ route('hr.reviews.marketing.bulk-delete') }}" class="btn btn-danger btn-sm"
                                        onclick="return confirm('هل أنت متأكد من حذف المراجعات المحددة؟')">
                                    <i class="fa fa-trash"></i> حذف المحدد نهائيًا
                                </button>
                            </div>

                            <div class="select-all-container">
                                <div class="form-check">
                                    <input class="form-check-input select-all" type="checkbox" id="selectAllMarketing" data-target="marketingCheckbox">
                                    <label class="form-check-label" for="selectAllMarketing">تحديد الكل</label>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>تحديد</th>
                                            <th>#</th>
                                            <th>الموظف</th>
                                            <th>المقيم</th>
                                            <th>تاريخ المراجعة</th>
                                            <th>تاريخ الحذف</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($marketingReviews as $review)
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input marketingCheckbox" type="checkbox" name="reviews[]" value="{{ $review->id }}" id="marketing{{ $review->id }}">
                                                    </div>
                                                </td>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $review->user->name }}</td>
                                                <td>{{ $review->reviewer->name }}</td>
                                                <td>{{ $review->created_at->format('Y-m-d') }}</td>
                                                <td>{{ $review->deleted_at->format('Y-m-d') }}</td>
                                                <td>
                                                    <a href="{{ route('hr.reviews.marketing.show', $review->id) }}" class="btn btn-sm btn-info" title="عرض">
                                                        <i class="fa fa-eye"></i> عرض
                                                    </a>
                                                    <form action="{{ route('hr.reviews.marketing.restore', $review->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" title="استعادة" onclick="return confirm('هل أنت متأكد من استعادة هذه المراجعة؟')">
                                                            <i class="fa fa-trash-restore"></i> استعادة
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('hr.reviews.marketing.delete', $review->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="حذف نهائي">
                                                            <i class="fa fa-trash"></i> حذف نهائي
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fa fa-headset me-2"></i>مراجعات فريق خدمة العملاء المحذوفة</h5>
                </div>
                <div class="card-body">
                    @if($customerServiceReviews->isEmpty())
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-2"></i>لا توجد مراجعات محذوفة لفريق خدمة العملاء
                        </div>
                    @else
                        <form action="{{ route('hr.reviews.customer-service.bulk-restore') }}" method="POST" id="customerServiceBulkForm">
                            @csrf
                            <div class="bulk-actions">
                                <button type="submit" formaction="{{ route('hr.reviews.customer-service.bulk-restore') }}" class="btn btn-success btn-sm">
                                    <i class="fa fa-trash-restore"></i> استعادة المحدد
                                </button>
                                <button type="submit" formaction="{{ route('hr.reviews.customer-service.bulk-delete') }}" class="btn btn-danger btn-sm"
                                        onclick="return confirm('هل أنت متأكد من حذف المراجعات المحددة؟')">
                                    <i class="fa fa-trash"></i> حذف المحدد نهائيًا
                                </button>
                            </div>

                            <div class="select-all-container">
                                <div class="form-check">
                                    <input class="form-check-input select-all" type="checkbox" id="selectAllCustomerService" data-target="customerServiceCheckbox">
                                    <label class="form-check-label" for="selectAllCustomerService">تحديد الكل</label>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>تحديد</th>
                                            <th>#</th>
                                            <th>الموظف</th>
                                            <th>المقيم</th>
                                            <th>تاريخ المراجعة</th>
                                            <th>تاريخ الحذف</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($customerServiceReviews as $review)
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input customerServiceCheckbox" type="checkbox" name="reviews[]" value="{{ $review->id }}" id="customerService{{ $review->id }}">
                                                    </div>
                                                </td>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $review->user->name }}</td>
                                                <td>{{ $review->reviewer->name }}</td>
                                                <td>{{ $review->created_at->format('Y-m-d') }}</td>
                                                <td>{{ $review->deleted_at->format('Y-m-d') }}</td>
                                                <td>
                                                    <a href="{{ route('hr.reviews.customer-service.show', $review->id) }}" class="btn btn-sm btn-info" title="عرض">
                                                        <i class="fa fa-eye"></i> عرض
                                                    </a>
                                                    <form action="{{ route('hr.reviews.customer-service.restore', $review->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" title="استعادة" onclick="return confirm('هل أنت متأكد من استعادة هذه المراجعة؟')">
                                                            <i class="fa fa-trash-restore"></i> استعادة
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('hr.reviews.customer-service.delete', $review->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="حذف نهائي">
                                                            <i class="fa fa-trash"></i> حذف نهائي
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fa fa-sync me-2"></i>مراجعات فريق التنسيق المحذوفة</h5>
                </div>
                <div class="card-body">
                    @if($coordinationReviews->isEmpty())
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-2"></i>لا توجد مراجعات محذوفة لفريق التنسيق
                        </div>
                    @else
                        <form action="{{ route('hr.reviews.coordination.bulk-restore') }}" method="POST" id="coordinationBulkForm">
                            @csrf
                            <div class="bulk-actions">
                                <button type="submit" formaction="{{ route('hr.reviews.coordination.bulk-restore') }}" class="btn btn-success btn-sm">
                                    <i class="fa fa-trash-restore"></i> استعادة المحدد
                                </button>
                                <button type="submit" formaction="{{ route('hr.reviews.coordination.bulk-delete') }}" class="btn btn-danger btn-sm"
                                        onclick="return confirm('هل أنت متأكد من حذف المراجعات المحددة؟')">
                                    <i class="fa fa-trash"></i> حذف المحدد نهائيًا
                                </button>
                            </div>

                            <div class="select-all-container">
                                <div class="form-check">
                                    <input class="form-check-input select-all" type="checkbox" id="selectAllCoordination" data-target="coordinationCheckbox">
                                    <label class="form-check-label" for="selectAllCoordination">تحديد الكل</label>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>تحديد</th>
                                            <th>#</th>
                                            <th>الموظف</th>
                                            <th>المقيم</th>
                                            <th>تاريخ المراجعة</th>
                                            <th>تاريخ الحذف</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($coordinationReviews as $review)
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input coordinationCheckbox" type="checkbox" name="reviews[]" value="{{ $review->id }}" id="coordination{{ $review->id }}">
                                                    </div>
                                                </td>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $review->user->name }}</td>
                                                <td>{{ $review->reviewer->name }}</td>
                                                <td>{{ $review->created_at->format('Y-m-d') }}</td>
                                                <td>{{ $review->deleted_at->format('Y-m-d') }}</td>
                                                <td>
                                                    <a href="{{ route('hr.reviews.coordination.show', $review->id) }}" class="btn btn-sm btn-info" title="عرض">
                                                        <i class="fa fa-eye"></i> عرض
                                                    </a>
                                                    <form action="{{ route('hr.reviews.coordination.restore', $review->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" title="استعادة" onclick="return confirm('هل أنت متأكد من استعادة هذه المراجعة؟')">
                                                            <i class="fa fa-trash-restore"></i> استعادة
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('hr.reviews.coordination.delete', $review->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="حذف نهائي">
                                                            <i class="fa fa-trash"></i> حذف نهائي
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Two-Factor Authentication Modal -->
<div class="modal fade two-factor-modal" id="twoFactorModal" tabindex="-1" aria-labelledby="twoFactorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="twoFactorModalLabel">التحقق بخطوتين</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="twoFactorForm" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="code" class="form-label">أدخل رمز التحقق بخطوتين</label>
                        <input type="text" class="form-control" id="code" name="code" required maxlength="6" placeholder="******">
                        <div class="invalid-feedback" id="codeError">
                            رمز التحقق غير صحيح
                        </div>
                    </div>
                    <input type="hidden" id="formTarget" name="formTarget" value="">
                    <input type="hidden" id="reviewType" name="reviewType" value="">
                    <input type="hidden" id="reviewId" name="reviewId" value="">
                    <div class="text-center mt-3">
                        <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize select all functionality
        $('.select-all').on('change', function() {
            const target = $(this).data('target');
            $('.' + target).prop('checked', $(this).prop('checked'));
        });

        // Update "select all" checkbox when individual checkboxes change
        $('.technicalCheckbox, .marketingCheckbox, .customerServiceCheckbox, .coordinationCheckbox').on('change', function() {
            const checkboxClass = $(this).attr('class').split(' ')[1];
            const allChecked = $('.' + checkboxClass + ':checked').length === $('.' + checkboxClass).length;
            $('#selectAll' + checkboxClass.replace('Checkbox', '')).prop('checked', allChecked);
        });

        // Override form submissions for bulk actions
        $('button[formaction*="bulk-delete"]').on('click', function(e) {
            e.preventDefault();
            const form = $(this).closest('form');
            const checkedBoxes = form.find('input[type="checkbox"]:checked:not(.select-all)');
            const checkedCount = checkedBoxes.length;

            if (checkedCount <= 0) {
                alert('يرجى تحديد مراجعة واحدة على الأقل');
                return false;
            }

            if (!confirm('هل أنت متأكد من تنفيذ هذا الإجراء على ' + checkedCount + ' مراجعة؟')) {
                return false;
            }

            // Collect all checked review IDs
            const reviewIds = [];
            checkedBoxes.each(function() {
                reviewIds.push($(this).val());
            });

            // Store form action for submission
            const formAction = $(this).attr('formaction');

            // Show 2FA modal
            $('#formTarget').val('bulk');
            $('#reviewType').val(formAction.split('.').slice(-2)[0]);
            $('#twoFactorModal').modal('show');

            // Store the reviews data and form action
            localStorage.setItem('selectedReviews', JSON.stringify(reviewIds));
            localStorage.setItem('formAction', formAction);
        });

        // Handle individual delete actions - intercept the delete forms
        $('form[action*="delete"]').on('submit', function(e) {
            e.preventDefault();

            const reviewId = $(this).attr('action').split('/').pop().split('?')[0];
            const reviewType = $(this).attr('action').split('/')[3]; // Extract type from URL

            // Show 2FA modal
            $('#formTarget').val('single');
            $('#reviewType').val(reviewType);
            $('#reviewId').val(reviewId);
            $('#twoFactorModal').modal('show');
        });

        // Reset the 2FA form when modal is shown
        $('#twoFactorModal').on('show.bs.modal', function() {
            $('#code').val('');
            $('#code').removeClass('is-invalid');
            $('#codeError').hide();
        });

        // Handle 2FA form submission
        $('#twoFactorForm').on('submit', function(e) {
            e.preventDefault();
            const formTarget = $('#formTarget').val();
            const reviewType = $('#reviewType').val();
            const reviewId = $('#reviewId').val();

            let formAction, formData;

            if (formTarget === 'bulk') {
                // Get the stored reviews and action
                const reviewIds = JSON.parse(localStorage.getItem('selectedReviews'));
                formAction = localStorage.getItem('formAction');

                // Create form data
                formData = new FormData();
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                formData.append('code', $('#code').val());

                // Add each review ID
                reviewIds.forEach(id => {
                    formData.append('reviews[]', id);
                });
            } else {
                // For single item actions
                formData = new FormData();
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                formData.append('code', $('#code').val());
                formData.append('_method', 'DELETE'); // Important for delete operations
                formAction = "{{ route('hr.reviews.technical.delete', ':id') }}".replace('technical', reviewType).replace(':id', reviewId);
            }

            // Disable the submit button and show loading
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.text();
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري المعالجة...');

            // Send the request
            $.ajax({
                url: formAction,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Clean up and reload
                    localStorage.removeItem('selectedReviews');
                    localStorage.removeItem('formAction');
                    window.location.reload();
                },
                error: function(xhr) {
                    // Re-enable the button
                    submitBtn.prop('disabled', false).text(originalText);

                    if (xhr.status === 422) {
                        $('#codeError').show();
                        $('#code').addClass('is-invalid');
                    } else {
                        alert('حدث خطأ أثناء معالجة طلبك. يرجى المحاولة مرة أخرى.');
                        console.error(xhr);
                    }
                }
            });
        });

        // Add animation to cards
        $('.card').each(function(i) {
            $(this).delay(i * 100).animate({
                opacity: 1,
                transform: 'translateY(0)'
            }, 500);
        });
    });
</script>
@endpush
