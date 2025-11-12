(function () {
    if (typeof window.VILLEGAS_COURSE_CONSOLE === 'undefined') {
        return;
    }

    var courseData = window.VILLEGAS_COURSE_CONSOLE;

    console.log('Course Number of Lessons:', courseData.totalLessons);
    console.log('Lessons Completed:', courseData.lessonsCompleted);
    console.log('Final Quiz id:', courseData.finalQuizId);
})();
