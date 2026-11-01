# JasperReports Parameters Map & Database Schema Reference Guide
**School Reporting Department Hub — Multi-Tenant Academic & JasperReports Engine**

---

## 1. Overview

When designing reports in **Jaspersoft Studio** for this system, you can use:
1. **Direct MySQL Queries (`<queryString language="SQL">`)**: Jasper connects directly to MySQL via JDBC (`mysql-connector-j-8.3.0.jar`), filters by `$P{P_STUDENT_ID}` or other parameters, and fetches live rows.
2. **Pre-computed JSON Data Source (`JsonDataSource`)**: For complex offline/pre-aggregated formulas without database queries.

---

## 2. Master Parameters Map

Declare these parameters in the **Outline > Parameters** panel in Jaspersoft Studio:

| Parameter Name (`$P{...}`) | Java Class (`class="..."`) | Default Value Expression | Description | Typical Usage in Reports |
| :--- | :--- | :--- | :--- | :--- |
| **`P_STUDENT_ID`** | `java.lang.Integer` | `110` | Primary key `id` in `students` table | **SQL Query Filter**: `WHERE s.id = $P{P_STUDENT_ID}` |
| **`P_ADMISSION_NUMBER`** | `java.lang.String` | `"SGIS-2026-101"` | Student unique admission code | **SQL Filter / Title**: `s.admission_number` |
| **`P_STUDENT_NAME`** | `java.lang.String` | `"Student Full Name"` | Full student name | **Title Band**: Student metadata header |
| **`P_CLASS_ID`** | `java.lang.Integer` | `1` | Foreign key `id` in `school_classes` | **SQL Filter**: `WHERE c.id = $P{P_CLASS_ID}` |
| **`P_CLASS_NAME`** | `java.lang.String` | `"Grade 10 - Section A"` | Class / Section display name | **Title Band**: Student class label |
| **`P_HOMEROOM_TEACHER`** | `java.lang.String` | `"Homeroom Teacher"` | Teacher assigned to class | **Title Band**: Teacher info |
| **`P_SCHOOL_ID`** | `java.lang.String` | `"SCH-SGIS-GAM"` | Unique school tenant identifier | **Multi-Tenant Filter**: `WHERE s.school_id = $P{P_SCHOOL_ID}` |
| **`P_SCHOOL_CODE`** | `java.lang.String` | `"SGIS"` | Short school code (e.g. SGIS, GV) | **Header / Badges**: School short code |
| **`P_SCHOOL_NAME`** | `java.lang.String` | `"Sun of Glory"` | Official school institutional name | **Report Title / Banner**: Top school header |
| **`P_ACADEMIC_YEAR`** | `java.lang.String` | `"2026-2027"` | Academic cycle string | **SQL Filter**: `AND gr.academic_cycle = $P{P_ACADEMIC_YEAR}` |
| **`P_TERM_NAME`** | `java.lang.String` | `"Term 1"` | Active term or trimester | **SQL Filter**: `AND gr.term_name = $P{P_TERM_NAME}` |
| **`P_DATE`** | `java.lang.String` | `"November 1, 2026"` | Report generation / issue date | **Title Band**: Issue date field |
| **`P_GPA`** | `java.lang.String` | `"3.85"` | Cumulative Grade Point Average | **Summary Band**: GPA box / badge |
| **`P_TOTAL_CREDITS`** | `java.lang.String` | `"6.5"` | Total credits earned | **Summary Band**: Credits total |
| **`P_SUMMER_COURSES`** | `java.lang.String` | `""` | Summer course records / notices | **Summary Band**: Extra academic notes |
| **`P_ABSENT_T1`** | `java.lang.Object` | `0` | Unexcused / Total absences in T1 | **Attendance Grid**: Trimester 1 absences |
| **`P_ABSENT_T2`** | `java.lang.Object` | `0` | Unexcused / Total absences in T2 | **Attendance Grid**: Trimester 2 absences |
| **`P_ABSENT_T3`** | `java.lang.Object` | `0` | Unexcused / Total absences in T3 | **Attendance Grid**: Trimester 3 absences |
| **`P_SCALE_NAME`** | `java.lang.String` | `"PERCENTAGE_100"` | Active grading scale name | **Legend Band**: Grading scale key |
| **`P_LOGO_PATH`** | `java.lang.String` | `""` | Disk path to official school logo | **Image Expression**: `<imageExpression><![CDATA[$P{P_LOGO_PATH}]]></imageExpression>` |
| **`P_COGNIA_LOGO_PATH`** | `java.lang.String` | `""` | Disk path to Cognia seal image | **Image Expression**: `<imageExpression><![CDATA[$P{P_COGNIA_LOGO_PATH}]]></imageExpression>` |
| **`P_LEGAL_REP_NAME`** | `java.lang.String` | `"Mohamed Tayseer Mattar"`| Superintendent / Legal rep name | **Signature Band**: Legal signature label |
| **`P_DIRECTOR_NAME`** | `java.lang.String` | `"Ragia Al Akhal"` | School Principal / Director name | **Signature Band**: Principal signature label |
| **`P_FOOTER_ADDRESS_IMAGE`**| `java.lang.String` | `""` | Disk path to footer address banner| **Page Footer Band**: Bottom address image |

---

## 3. Database Schema & Tables Reference

### 3.1. `students` Table
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | `BIGINT (PK)` | Student unique ID (`$P{P_STUDENT_ID}`) |
| `school_id` | `VARCHAR(50)` | School tenant foreign key |
| `class_id` | `BIGINT (FK)` | Current class section |
| `admission_number` | `VARCHAR(50)` | Student admission / student code |
| `full_name` | `VARCHAR(150)` | Full student name |
| `gender` | `VARCHAR(10)` | `'MALE'` or `'FEMALE'` |
| `status` | `VARCHAR(20)` | `'ACTIVE'`, `'WITHDRAWN'`, `'GRADUATED'` |

### 3.2. `school_classes` Table
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | `BIGINT (PK)` | Class unique ID |
| `school_id` | `VARCHAR(50)` | School tenant foreign key |
| `grade_id` | `BIGINT (FK)` | Reference to `school_grade_levels.id` |
| `class_name` | `VARCHAR(100)`| e.g. `'Grade 10 - Section A'` |
| `homeroom_teacher` | `VARCHAR(150)`| Homeroom teacher's name |
| `max_capacity` | `INT` | Class capacity |

### 3.3. `master_subjects` Table
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | `BIGINT (PK)` | Subject master ID |
| `school_id` | `VARCHAR(50)` | School tenant foreign key |
| `subject_name` | `VARCHAR(150)`| Name (e.g. `'English Language'`, `'Mathematics'`) |
| `subject_code` | `VARCHAR(50)` | Short code (e.g. `'ENG-10'`, `'MATH-10'`) |
| `department` | `VARCHAR(100)`| e.g. `'Languages'`, `'Sciences'`, `'Mathematics'` |
| `max_score` | `DECIMAL(6,2)`| Maximum attainable score (usually `100.00`) |
| `default_credits`| `DECIMAL(4,2)`| Credit hours weighting (e.g. `1.00`, `0.50`) |

### 3.4. `class_subject_assignments` Table (Curriculum Mapping)
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | `BIGINT (PK)` | Assignment ID linking Class to Subject |
| `class_id` | `BIGINT (FK)` | Reference to `school_classes.id` |
| `subject_id` | `BIGINT (FK)` | Reference to `master_subjects.id` |
| `assigned_teacher` | `VARCHAR(150)`| Teacher teaching this course |
| `credit_hours` | `DECIMAL(4,2)`| Course credits |
| `cw_weight` | `DECIMAL(5,2)`| Coursework weight percentage (e.g. `40.00`) |
| `exam_weight` | `DECIMAL(5,2)`| Exam weight percentage (e.g. `60.00`) |
| `passing_mark` | `DECIMAL(5,2)`| Passing threshold percentage (e.g. `60.00`) |
| `display_order` | `INT` | Sort order on report cards |

### 3.5. `grade_records` Table (Grades & Scores)
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | `BIGINT (PK)` | Grade record ID |
| `student_id` | `BIGINT (FK)` | Reference to `students.id` |
| `class_subject_assignment_id` | `BIGINT (FK)` | Reference to `class_subject_assignments.id` |
| `academic_cycle` | `VARCHAR(30)` | e.g. `'2026-2027'` |
| `term_name` | `VARCHAR(50)` | `'Term 1'`, `'Term 2'`, `'Term 3'`, etc. |
| `cw_score` | `DECIMAL(6,2)`| Score obtained in Coursework |
| `exam_score` | `DECIMAL(6,2)`| Score obtained in Term Exam |
| `total_score` | `DECIMAL(6,2)`| Total weighted score |
| `letter_grade` | `VARCHAR(10)` | Evaluated letter grade (`A*`, `A`, `B+`, etc.) |
| `gpa_point` | `DECIMAL(4,2)`| GPA quality points (e.g. `4.00`, `3.50`) |
| `teacher_remarks` | `TEXT` | Specific remarks by subject teacher |

### 3.6. `attendance_records` Table
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | `BIGINT (PK)` | Attendance record ID |
| `student_id` | `BIGINT (FK)` | Reference to `students.id` |
| `attendance_date` | `DATE` | Date of attendance |
| `status` | `VARCHAR(30)` | `'PRESENT'`, `'EXCUSED'`, `'UNEXCUSED'`, `'TARDY'` |
| `session_type` | `VARCHAR(30)` | `'FULL_DAY'`, `'MORNING'`, `'AFTERNOON'` |

---

## 4. Ready-to-Use SQL Query Templates for Jaspersoft Studio

### Template A: Standard Subject Marks Report (Single Term)
```sql
SELECT 
    s.id AS student_id,
    s.admission_number,
    s.full_name AS student_name,
    c.class_name,
    c.homeroom_teacher,
    sub.subject_name,
    sub.subject_code,
    csa.credit_hours,
    csa.cw_weight,
    csa.exam_weight,
    COALESCE(gr.cw_score, 0.0) AS cw_score,
    COALESCE(gr.exam_score, 0.0) AS exam_score,
    COALESCE(gr.total_score, 0.0) AS total_score,
    COALESCE(gr.letter_grade, '-') AS letter_grade,
    COALESCE(gr.gpa_point, 0.0) AS gpa_point,
    gr.teacher_remarks
FROM students s
JOIN school_classes c ON s.class_id = c.id
JOIN class_subject_assignments csa ON csa.class_id = c.id
JOIN master_subjects sub ON csa.subject_id = sub.id
LEFT JOIN grade_records gr ON gr.student_id = s.id 
                          AND gr.class_subject_assignment_id = csa.id 
                          AND gr.academic_cycle = $P{P_ACADEMIC_YEAR}
                          AND gr.term_name = $P{P_TERM_NAME}
WHERE s.id = $P{P_STUDENT_ID}
ORDER BY csa.display_order ASC, sub.subject_name ASC
```

---

### Template B: Trimester Breakdown Report (SGIS Style: T1, T2, T3 & Cumulative)
```sql
SELECT 
    sub.subject_name AS subjectName,
    COALESCE(CONCAT(ROUND(MAX(CASE WHEN gr.term_name IN ('Term 1', 'Trimester 1') THEN gr.total_score END), 0), '%'), '--') AS t1Percent,
    COALESCE(MAX(CASE WHEN gr.term_name IN ('Term 1', 'Trimester 1') THEN gr.letter_grade END), '--') AS t1Grade,
    COALESCE(CONCAT(ROUND(MAX(CASE WHEN gr.term_name IN ('Term 2', 'Trimester 2') THEN gr.total_score END), 0), '%'), '--') AS t2Percent,
    COALESCE(MAX(CASE WHEN gr.term_name IN ('Term 2', 'Trimester 2') THEN gr.letter_grade END), '--') AS t2Grade,
    COALESCE(CONCAT(ROUND(MAX(CASE WHEN gr.term_name IN ('Term 3', 'Trimester 3') THEN gr.total_score END), 0), '%'), '--') AS t3Percent,
    COALESCE(MAX(CASE WHEN gr.term_name IN ('Term 3', 'Trimester 3') THEN gr.letter_grade END), '--') AS t3Grade,
    COALESCE(CONCAT(ROUND(AVG(gr.total_score), 1), '%'), '--') AS cumulativePercent,
    COALESCE(MAX(CASE WHEN gr.term_name IN ('Term 1', 'Trimester 1') THEN gr.letter_grade END), '--') AS cumulativeGrade,
    COALESCE(csa.credit_hours, 1.0) AS credit
FROM students s
JOIN class_subject_assignments csa ON csa.class_id = s.class_id
JOIN master_subjects sub ON csa.subject_id = sub.id
LEFT JOIN grade_records gr ON gr.student_id = s.id 
                          AND gr.class_subject_assignment_id = csa.id 
                          AND gr.academic_cycle = $P{P_ACADEMIC_YEAR}
WHERE s.id = $P{P_STUDENT_ID}
GROUP BY sub.id, sub.subject_name, csa.credit_hours, csa.display_order
ORDER BY csa.display_order ASC, sub.subject_name ASC
```

---

## 5. Jaspersoft Studio MySQL Connection Setup

In Jaspersoft Studio:
1. Click **Repository Explorer > Data Adapters > Create Data Adapter**.
2. Select **Database JDBC Connection**.
3. Fill in the connection settings:
   * **JDBC Driver**: `com.mysql.cj.jdbc.Driver`
   * **JDBC URL**: `jdbc:mysql://127.0.0.1:3306/u118583162_school_reports?useSSL=false&allowPublicKeyRetrieval=true&serverTimezone=UTC`
   * **Username**: `root` (or your MySQL user)
   * **Password**: *(your MySQL password)*
4. Click **Test Connection**. Once successful, click **Finish**.
5. When designing your report, click **Dataset and Query Dialog**, paste the SQL query from above, and click **Read Fields**!
