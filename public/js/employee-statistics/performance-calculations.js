/**
 * Performance Calculations JavaScript
 * حساب مؤشرات الأداء ونسب التقييم للموظفين
 */

// دالة حساب النتيجة النهائية للأداء
function calculateOverallScore(data) {
    let attendanceScore = data.statistics.attendance_percentage;

    let punctualityScore = 100;
    if (data.statistics.delays > 120) {
        let excessDelays = data.statistics.delays - 120;
        punctualityScore = Math.max(0, 100 - ((excessDelays / 120) * 100));
    }

    let workingHoursScore = 100;
    if (typeof data.statistics.average_working_hours !== 'undefined') {
        workingHoursScore = Math.min(100, Math.round((data.statistics.average_working_hours / 8) * 100));
    }

    // استخدام النسب الجديدة للتقييم (الحضور 45%، الانضباط 20%، ساعات العمل 35%)
    let overallScore = Math.round((attendanceScore * 0.45) + (punctualityScore * 0.2) + (workingHoursScore * 0.35));
    return overallScore;
}
