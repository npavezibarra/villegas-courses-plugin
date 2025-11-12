# Final Quiz Access Investigation

## 1. Course and Final Quiz Identifiers
- The Final Quiz button rendered on the single-course dashboard comes from `mostrar_comprar_stats()`, which resolves the current course with `get_the_ID()` (`$course_id`) before pulling quiz metadata. 【F:parts/comprar-stats.php†L71-L104】
- The Final Quiz ID displayed on the CTA is the result of `PoliteiaCourse::getFinalQuizId( $course_id )`, i.e., the `_final_quiz_id` course meta. The quiz permalink shown on the button is generated from this ID, and the button remains disabled until prerequisites are met. 【F:parts/comprar-stats.php†L179-L216】
- Because the repository does not ship with a seed database, the concrete post IDs cannot be enumerated here. Retrieve them in production with `get_post_meta( $course_id, '_final_quiz_id', true )` or by inspecting the rendered markup on the course page.

## 2. Course Builder Consistency (`ld_course_steps`)
- Administrative helpers scan `ld_course_steps` to ensure the final quiz is registered as a course step; if it is missing they can append it back. 【F:includes/admin/class-course-checklist-handler.php†L240-L370】
- Validating live data requires reading `get_post_meta( $course_id, 'ld_course_steps', true )`. Confirm the Final Quiz ID found above appears in that structure (either as a top-level key or nested array entry). A mismatch will prevent LearnDash from exposing the quiz and must be corrected in the builder.

## 3. Progress Calculation Source
- Progress for the Final Quiz gate leverages `villegas_get_course_progress_percentage()`, which wraps `learndash_course_progress()`/`learndash_user_get_course_progress()`. 【F:functions.php†L433-L472】
- The course widget itself independently recomputes a percentage as `completed_steps / total_steps` using `learndash_get_course_steps()`. This LearnDash API counts *all* course steps—including quizzes—so the Final Quiz itself depresses the percentage until it is completed. 【F:parts/comprar-stats.php†L100-L123】
- Result: quizzes are currently included in the “lessons completed” metric and block access even when every lesson is done. Progress should instead be derived from lesson-only counts (e.g., `learndash_get_lesson_list()` + `learndash_is_lesson_complete()`), or by filtering the step list to exclude `sfwd-quiz` entries before computing totals.

## 4. User Activity Audit
- The plugin checks quiz attempts via the `learndash_user_activity` tables when drawing buttons, but the repository contains no anonymised production records. 【F:parts/comprar-stats.php†L182-L208】
- To identify specific lessons lacking `activity_completed` timestamps, run in production:
  ```sql
  SELECT post_id
  FROM wp_learndash_user_activity
  WHERE user_id = ? AND course_id = ? AND activity_type = 'lesson'
    AND (activity_completed IS NULL OR activity_completed = 0);
  ```
  Any returned lesson IDs explain why completion stays below 100%, keeping the Final Quiz locked until each lesson is marked complete.

## 5. LearnDash / Quiz Settings That Can Block Access
- Front-end access is additionally restricted by `villegas_enforce_quiz_access_control()`, which enforces login, enrollment (`sfwd_lms_has_access()`), and 100% progress before letting the Final Quiz load. 【F:functions.php†L518-L614】
- The check relies purely on course progress and purchase state; no custom drip, prerequisites, or LearnDash “linear progression” overrides are enforced here. Confirm LearnDash course settings (progression mode, lesson release schedule, prerequisites) in wp-admin, as those native flags can still hide lessons and prevent 100% completion if enabled.

## 6. Final Quiz CTA / Gating Logic
- The widget shows three states:
  1. **Unlocked** – user enrolled, course progress ≥ 100%, no passing attempt yet ⇒ button links directly to the Final Quiz permalink. 【F:parts/comprar-stats.php†L200-L204】
  2. **Completed** – latest quiz attempt recorded ⇒ score card replaces button. 【F:parts/comprar-stats.php†L205-L209】
  3. **Locked** – all other cases ⇒ disabled button with tooltip “Completa todas las lecciones…”. 【F:parts/comprar-stats.php†L210-L216】
- Separately, trying to access the quiz URL triggers the template redirect gate above, which displays one of three blocking messages depending on login state, course ownership, and progress percentage. 【F:functions.php†L569-L614】

## 7. WooCommerce Linkage and Enrollment Checks
- The plugin maps courses to WooCommerce products via `_linked_woocommerce_product` first, falling back to products whose `_related_course` meta references the course. Invalid links are pruned. 【F:functions.php†L351-L396】
- `villegas_user_has_course_access()` first delegates to LearnDash enrollment (`sfwd_lms_has_access`), then checks `wc_customer_bought_product()` against the resolved product ID. Either condition yields access, which both the widget and the quiz gate respect. 【F:functions.php†L407-L419】
- Validate in production that the course has a published product linked through one of those meta keys and that the affected user owns a completed order for it (or is manually enrolled).

## Root Cause & Fix Recommendation
- **Root cause:** Course progress is calculated against all LearnDash steps; the Final Quiz itself is counted as an outstanding step, so the percentage never reaches 100% before the quiz is passed, and the access gate keeps the button in a locked state. 【F:parts/comprar-stats.php†L100-L216】【F:functions.php†L433-L614】
- **Minimal fix:** Rework the progress calculation used for gating so it only tallies lessons (and topics, if applicable) while excluding quizzes. For example, filter `learndash_get_course_steps()` to remove `sfwd-quiz` entries before counting totals, or rely on LearnDash lesson-specific helpers. Once the percentage reflects lesson completion only, the Final Quiz button will unlock as soon as the student finishes every lesson.
