function initializeCountdowns() {
    const countdowns = document.querySelectorAll('.countdown');
    countdowns.forEach(countdown => {
        startCountdown(countdown);
    });
}

function handleReturnStatus() {
    document.querySelectorAll('.return-btn, .reset-btn').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            const status = this.dataset.status;

            if (status === '2' && !confirm('هل أنت متأكد من تسجيل عدم عودة الموظف؟')) {
                return;
            }

            fetch(`/permission-requests/${requestId}/return-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ return_status: status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'حدث خطأ أثناء تحديث حالة العودة');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء تحديث حالة العودة');
            });
        });
    });
}

function markAsReturned(requestId) {
    fetch(`/permission-requests/${requestId}/mark-returned`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'حدث خطأ أثناء تسجيل العودة');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء تسجيل العودة');
    });
}

function resetStatus(requestId, type) {
    if (confirm('هل أنت متأكد من إعادة تعيين هذا الرد؟')) {
        fetch(`/permission-requests/${requestId}/reset-${type}-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                _token: document.querySelector('meta[name="csrf-token"]').content
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'حدث خطأ أثناء إعادة تعيين الرد');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء إعادة تعيين الرد');
        });
    }
}

function checkEndOfDay() {
    const requests = document.querySelectorAll('.request-row');
    const requestIds = Array.from(requests)
        .filter(row => {
            const statusBadge = row.querySelector('.badge');
            return statusBadge && statusBadge.textContent.trim() === 'موافق';
        })
        .map(row => row.dataset.requestId)
        .filter(Boolean);

    if (requestIds.length > 0) {
        fetch('/permission-requests/check-end-of-day', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ request_ids: requestIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.updated_requests?.length > 0) {
                location.reload();
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initializeCountdowns();
    handleReturnStatus();

    $('.datatable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Arabic.json"
        }
    });

    $('.select2').select2({
        dir: "rtl"
    });

    function checkEndOfDayRequests() {
        const requestIds = [];
        $('.mark-not-returned').each(function() {
            requestIds.push($(this).data('request-id'));
        });

        if (requestIds.length > 0) {
            $.ajax({
                url: '/permission-requests/check-end-of-day',
                type: 'POST',
                data: {
                    request_ids: requestIds,
                    _token: document.querySelector('meta[name="csrf-token"]').content
                },
                success: function(response) {
                    if (response.updated_requests && response.updated_requests.length > 0) {
                        location.reload();
                    }
                }
            });
        }
    }

    checkEndOfDayRequests();
    setInterval(checkEndOfDayRequests, 60000);
});
