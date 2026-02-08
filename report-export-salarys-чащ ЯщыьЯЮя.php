<?php
//session_start();
($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();
//if(!empty($_SESSION['user']['id']) ){
    $allowed_branches = $User->allBranches($User->branches);
    $branch_ids = array_keys($allowed_branches); // استخراج المفاتيح
    $allowed_branch = implode(',', $branch_ids);
  
$parma = array();


$results_note = ["report_time" => $now_date]; 
//$results_note += ['selected_period' => 'حتى '.$today_date]; 
$results_note += ['selected_period' => '']; 
$results_note += ['selected_section' => []]; 
$results_note += ['selected_jobtitle' => []]; 
$results_note += ['selected_degree' => []];  
$results_note += ['selected_period' => []]; 
$results_note += ['selected_group' => []];  
$results_note += ['selected_branch' => []]; 
$results_note += ['name' =>'']; 
$filter = false;
  
    if(!empty($_POST["date_range"])){
        $range = explode(' - ',$_POST["date_range"]);
        $date_range[0] = date('Y-m-d',strtotime($range[0]));
        $date_range[1] = date('Y-m-d',strtotime($range[1]));
    } 
    else
    {
     $date_range[0]=$today_date;   
     $date_range[1] =$today_date;
    }

$query = 
"WITH RECURSIVE DateRange AS (
    SELECT DATE('$date_range[0]') AS Date
    UNION ALL
    SELECT DATE_ADD(Date, INTERVAL 1 DAY)
    FROM DateRange
    WHERE Date < '$date_range[1]'
),

-- تحديد فترات الراتب لكل موظف مع منع التكرار
SalaryPeriods AS (
    SELECT 
        u.UserID,
        GREATEST(r.conform_date, '$date_range[0]') AS period_start,
        LEAST(COALESCE(r.new_e_date, '$date_range[1]'), '$date_range[1]') AS period_end,
        r.Salary,
        DATEDIFF(
            LEAST(COALESCE(r.new_e_date, '$date_range[1]'), '$date_range[1]'),
            GREATEST(r.conform_date, '$date_range[0]')
        ) + 1 AS days_in_period,
        r.BranchID,
        r.Id AS renewal_id,
        ROW_NUMBER() OVER (
            PARTITION BY u.UserID 
            ORDER BY r.CreatedDate ASC, Id ASC
        ) AS rn
        from tblusers u
    JOIN tblremewal r ON r.Id = u.lastversion
    WHERE conform_date <= '$date_range[1]'
    AND (new_e_date IS NULL OR new_e_date >= '$date_range[0]')
    AND state IS NOT NULL
),


-- نستخدم فقط أحدث تسجيل لكل موظف
FilteredSalaryPeriods AS (
    SELECT 
        UserID, period_start, period_end, Salary, 
        days_in_period, BranchID, renewal_id
    FROM SalaryPeriods
    WHERE rn = 1
),

LeaveDates AS (
    SELECT 
        l.UserID,
        l.leave_start_date AS Date,
        l.leave_end_date,
        l.status,
        lt.type AS LeaveType,
        lt.Amount,
        lt.AmountType,
        u.BranchID,
        u.isemp,
        CONCAT(u.FirstName, ' ', u.LastName) AS emp_name
    FROM tblleaverequest l
    JOIN leaveclassification lt ON l.leavetype = lt.Id 
    JOIN tblusers u ON l.UserID = u.UserID
    WHERE l.status = 1
    AND u.BranchID IN ($allowed_branch)
    AND u.isemp IS NOT NULL
    AND l.CreatedDate between '$date_range[0]' and '$date_range[1]'
),

Holidays AS (
    SELECT  Date
    FROM holidays_day
    WHERE Date BETWEEN '$date_range[0]' AND '$date_range[1]'
),

alltotalhour AS (
    SELECT 
        d.UserID,
        d.Date,
        SEC_TO_TIME(SUM(TIME_TO_SEC(ss.total_work_hour))) AS totalhour
    FROM AllDates d
    JOIN tblusers u ON u.UserID = d.UserID
    JOIN tblremewal r ON r.Id = u.lastversion
    JOIN tbshift tt ON FIND_IN_SET(tt.ShiftID, r.shiftID) 
    JOIN shifts_schedule ss 
         ON ss.shift_id = tt.ShiftID
        AND d.Date BETWEEN ss.start_date AND ss.end_date
    WHERE r.state IS NOT NULL
    GROUP BY d.UserID, d.Date
),

AllEmployees AS (
    SELECT 
        e.UserID,
        e.lastversion,
        e.BranchID,
        e.isemp,
        CONCAT(e.FirstName, ' ', e.LastName) AS emp_name,
        a.Date, 
        MIN(a.Time) as Time,
        MIN(a.ID) as ID,
        p.ShiftID,
        CONCAT(p.ShiftID, ' ', p.ShiftName) as shift_name,
        ss.start_time as ShiftStartTime,
        ss.end_time as ShiftEndTime,
        MAX(p.NumFootprint) as NumFootprint,
        ss.total_work_hour as TotalWorkHour,
        s.allowed_late_minutes,
        s.allowed_early_leave,
        s.enable_half_day,
        s.half_day_minutes,
        s.absent_minutes,
        s.enable_early_half_day,
        s.early_half_day_minutes,
        s.early_absent_minutes,
        s.missing_checkout_action
    FROM 
        tblremewal eu
    JOIN 
         tblusers e ON  eu.Id=e.lastversion
    JOIN 
        attendancet a ON e.UserID = a.EmpID
    
    JOIN 
        tbshift p ON FIND_IN_SET(p.ShiftID, eu.shiftID) > 0
    JOIN 
        shift_setting s ON p.ShiftID = s.shift_id
    LEFT JOIN shifts_schedule AS ss ON ss.shift_id= p.shiftID


    WHERE e.BranchID IN ($allowed_branch)
    AND e.isemp IS NOT NULL 
    AND eu.state IS NOT NULL
    AND a.Date between '$date_range[0]' and '$date_range[1]'

    GROUP BY
        e.UserID, e.lastversion, e.BranchID, e.FirstName, e.LastName, a.Date, p.ShiftID, 
        p.ShiftName, ss.start_time, ss.end_time,
        ss.total_work_hour, s.allowed_late_minutes,
        s.allowed_early_leave, s.enable_half_day, s.half_day_minutes,
        s.absent_minutes, s.enable_early_half_day, s.early_half_day_minutes,
        s.early_absent_minutes, s.missing_checkout_action
),


    

EmployeeAttendance AS (
    SELECT 
        ae.UserID,
        ae.NumFootprint,
        ae.Date,
        ae.ShiftID,
        ae.shift_name,
        ae.ShiftStartTime,
        ae.ShiftEndTime,
        ae.TotalWorkHour,
        ae.allowed_late_minutes,
        ae.allowed_early_leave,
        ae.enable_half_day,
        ae.half_day_minutes,
        ae.absent_minutes,
        ae.enable_early_half_day,
        ae.early_half_day_minutes,
        ae.early_absent_minutes,
        ae.missing_checkout_action,
        (SELECT MIN(a1.Time)
         FROM attendancet a1
         WHERE a1.EmpID = ae.UserID
           AND a1.Date = ae.Date
           AND a1.Time BETWEEN ae.ShiftStartTime 
                           AND ADDTIME(ae.ShiftStartTime, SEC_TO_TIME(ae.allowed_late_minutes * 60))
         LIMIT 1) AS valid_checkin,
        
        (SELECT MAX(a2.Time)
         FROM attendancet a2
         WHERE a2.EmpID = ae.UserID
           AND a2.Date = ae.Date
           AND a2.Time BETWEEN SUBTIME(ae.ShiftEndTime, SEC_TO_TIME(ae.allowed_early_leave * 60))
                           AND ae.ShiftEndTime
         LIMIT 1) AS valid_checkout,
        
        (SELECT MIN(a3.Time)
        FROM attendancet a3
        WHERE a3.EmpID = ae.UserID
            AND a3.Date = ae.Date
            AND a3.Time >= ae.ShiftStartTime
            AND a3.Time <= ae.ShiftEndTime
        ) AS first_punch,

        (SELECT MAX(a4.Time)
         FROM attendancet a4
         WHERE a4.EmpID = ae.UserID
           AND a4.Date = ae.Date
         LIMIT 1) AS last_punch
    FROM AllEmployees ae
),

EmployeeLeaves AS (
    SELECT 
        l.UserID,
        l.leave_start_date AS LeaveStartDate,
        l.leave_end_date AS LeaveEndDate,
        l.status,
        lt.type AS LeaveType,
        lt.Amount,
        lt.AmountType,
        u.BranchID
    FROM tblleaverequest l
    JOIN leaveclassification lt ON l.leavetype = lt.Id
    JOIN tblusers u ON l.UserID = u.UserID
    WHERE l.status = 1
    AND u.BranchID IN ($allowed_branch)
    AND u.isemp IS NOT NULL
),

AllDates AS (
    SELECT 
        u.UserID, 
        dr.Date,
        u.BranchID
    FROM DateRange dr
    CROSS JOIN tblusers u
    JOIN tblremewal eu ON eu.Id = u.lastversion
    WHERE u.BranchID IN ($allowed_branch)
    AND u.isemp IS NOT NULL 
    AND eu.state IS NOT NULL
),

EmployeeStatus AS (
    SELECT 
        r.UserID,
        r.new_s_date,
        r.new_e_date,
        r.conform_date,
        u.IsDisabled,
        rs.DueDate AS termination_date,
        CASE 
            WHEN rs.type = 1 THEN 'استقالة'
            WHEN rs.type = 2 THEN 'فصل'
            ELSE NULL
        END AS termination_type,
        rs.Status AS termination_status
    FROM tblremewal r
    JOIN tblusers u ON r.Id = u.lastversion
    LEFT JOIN tblresignation rs ON r.UserID = rs.UserID
    WHERE u.BranchID IN ($allowed_branch)
    AND u.isemp IS NOT NULL
    AND r.state IS NOT NULL
),

EmployeeWorkHours AS (
    SELECT 
        d.UserID,
        sp.period_start,
        sp.period_end,
        sp.Salary,
        sp.days_in_period,
        -- d.Date,
        ath.totalhour,
       sum( CASE
            WHEN es.termination_type IS NOT NULL AND es.termination_status IS NOT NULL AND d.Date >= es.termination_date THEN 0
            
            WHEN es.new_e_date IS NOT NULL AND d.Date > es.new_e_date THEN 0
            
            WHEN es.IsDisabled IS NOT NULL THEN 0
            
            WHEN h.Date IS NOT NULL THEN TIME_TO_SEC(COALESCE(ea.TotalWorkHour, ath.totalhour, '08:00:00'))
            
            WHEN el.UserID IS NOT NULL THEN
                CASE
                    WHEN el.LeaveType = 1 THEN TIME_TO_SEC(COALESCE(ea.TotalWorkHour, ath.totalhour, '08:00:00'))
                    WHEN el.LeaveType = 2 THEN 
                        TIME_TO_SEC(COALESCE(ea.TotalWorkHour, ath.totalhour, '08:00:00')) * 
                        (1 - COALESCE(el.Amount, 0) / 100)
                    ELSE 0
                END
            
            WHEN ea.valid_checkin IS NOT NULL AND 
                 TIME_TO_SEC(TIMEDIFF(COALESCE(ea.valid_checkin, ea.ShiftStartTime), ea.ShiftStartTime)) / 60 <= COALESCE(ea.allowed_late_minutes, 0) AND
                 (ea.valid_checkout IS NOT NULL OR ea.NumFootprint = 1) AND
                 (ea.NumFootprint = 1 OR 
                  TIME_TO_SEC(TIMEDIFF(COALESCE(ea.ShiftEndTime, '00:00:00'), COALESCE(ea.valid_checkout, '00:00:00'))) / 60 <= COALESCE(ea.allowed_early_leave, 0))
            THEN TIME_TO_SEC(COALESCE(ea.TotalWorkHour, ath.totalhour, '08:00:00'))
            
            WHEN ea.enable_half_day = '3' AND 
                 TIME_TO_SEC(TIMEDIFF(COALESCE(ea.first_punch, ea.ShiftStartTime), ea.ShiftStartTime)) / 60 <= COALESCE(ea.half_day_minutes, 0)
            THEN TIME_TO_SEC(COALESCE(ea.TotalWorkHour, ath.totalhour, '08:00:00')) / 2
            
            WHEN ea.enable_early_half_day = '3' AND 
                 TIME_TO_SEC(TIMEDIFF(COALESCE(ea.ShiftEndTime, '00:00:00'), COALESCE(ea.last_punch, '00:00:00'))) / 60 <= COALESCE(ea.early_half_day_minutes, 0)
            THEN TIME_TO_SEC(COALESCE(ea.TotalWorkHour, ath.totalhour, '08:00:00')) / 2
            
            WHEN ea.enable_early_half_day = '1' AND ea.missing_checkout_action = '2'
            THEN TIME_TO_SEC(COALESCE(ea.TotalWorkHour, ath.totalhour, '08:00:00')) / 2
            
            WHEN ea.valid_checkin IS NOT NULL AND ea.valid_checkout IS NOT NULL THEN
                TIME_TO_SEC(TIMEDIFF(COALESCE(ea.valid_checkout, '00:00:00'), COALESCE(ea.valid_checkin, '00:00:00')))
            
            WHEN ea.valid_checkin IS NOT NULL AND ea.NumFootprint = 1 THEN
                TIME_TO_SEC(TIMEDIFF(COALESCE(ea.ShiftEndTime, '00:00:00'), COALESCE(ea.valid_checkin, '00:00:00')))
            
            ELSE 0
        END ) AS total_work_seconds
    FROM AllDates d
    JOIN FilteredSalaryPeriods sp ON d.UserID = sp.UserID AND d.Date BETWEEN sp.period_start AND sp.period_end
    LEFT JOIN EmployeeAttendance ea ON ea.UserID = d.UserID AND ea.Date = d.Date
    LEFT JOIN EmployeeLeaves el ON el.UserID = d.UserID AND d.Date BETWEEN el.LeaveStartDate AND el.LeaveEndDate
    LEFT JOIN Holidays h ON d.Date = h.Date  
    LEFT JOIN alltotalhour ath ON ath.UserID = d.UserID AND ath.Date = d.Date
    LEFT JOIN EmployeeStatus es ON es.UserID = d.UserID
    GROUP BY d.UserID, sp.period_start, sp.period_end, sp.Salary
)
,

PeriodSalary AS (
    SELECT 
        UserID,
        period_start,
        period_end,
        Salary,
        days_in_period,
        Salary * days_in_period / DAY(LAST_DAY('$date_range[1]')) AS period_salary
    FROM FilteredSalaryPeriods
),

Advances AS (
    SELECT 
        UserID, 
        SUM(Amount) AS total_advances, 
        MAX(currency) AS advance_currency
    FROM tblempadvances
    WHERE Status IS NOT NULL AND type = 1 AND CreatedDate between '$date_range[0]' and '$date_range[1]'
    GROUP BY UserID
),

remain_salary_tb AS (
SELECT *
FROM salary_before_this_month
WHERE DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(DATE_SUB('$date_range[1]', INTERVAL 1 MONTH), '%Y-%m')

),

Deductions AS (
    SELECT 
        d.for_what,
        d.Currency,
        d.UserID AS DeductionUserID,
        d.Amount,
        d.Status,
        d.extionsion,
        d.DueDate
    FROM tbldeductions d
    WHERE d.Status IS NOT NULL
    AND d.DueDate between '$date_range[0]' and '$date_range[1]'
),

Incentive AS (
    SELECT 
        i.for_what,
        i.Currency,
        i.UserID AS IncentiveUserID,
        i.Amount,
        i.AmountType,
        i.Status,
        i.extionsion,
        CASE 
            WHEN i.incentive_type = 1 THEN '$date_range[1]'
            ELSE i.DueDate
        END AS DueDateee
    FROM tblincentives i
    WHERE 
        i.Status IS NOT NULL
        AND (
            CASE 
                WHEN i.incentive_type = 1  AND i.DueDate <= '$date_range[1]' THEN '$date_range[1]'
                ELSE i.DueDate
            END
        ) BETWEEN '$date_range[0]' and '$date_range[1]' 
),

benefit AS (
    SELECT 
        b.for_what,
        b.Currency,
        b.UserID AS benefitUserID,
        b.Amount,
        b.AmountType,
        b.Status,
        b.extionsion,
        b.CreatedDate,
        CASE 
            WHEN b.beneft_type = 1 THEN '$date_range[1]'
            ELSE b.DueDate
        END AS DueDateee
    FROM tblbenefit b
    WHERE 
        b.Status IS NOT NULL
        AND (
            CASE 
                WHEN b.beneft_type = 1 AND b.DueDate <= '$date_range[1]' THEN '$date_range[1]'
                ELSE b.DueDate
            END
        ) BETWEEN '$date_range[0]' and '$date_range[1]' 
),

UserDeductions AS (
    SELECT 
        u.UserID,
        SUM(CASE 
            WHEN (d.for_what = 1 AND FIND_IN_SET(u.UserID, d.DeductionUserID) > 0) 
            OR (d.for_what = 2 AND FIND_IN_SET(t.GroupID, d.DeductionUserID) > 0 
                AND (d.extionsion IS NULL OR FIND_IN_SET(u.UserID, d.extionsion) = 0))
            OR (d.for_what = 3 AND FIND_IN_SET(t.GradeID, d.DeductionUserID) > 0 
                AND (d.extionsion IS NULL OR FIND_IN_SET(u.UserID, d.extionsion) = 0))
            OR (d.for_what = 4 AND FIND_IN_SET(t.SectionID, d.DeductionUserID) > 0 
                AND (d.extionsion IS NULL OR FIND_IN_SET(u.UserID, d.extionsion) = 0))
            OR (d.for_what = 5 AND FIND_IN_SET(t.jobtitleID, d.DeductionUserID) > 0 
                AND (d.extionsion IS NULL OR FIND_IN_SET(u.UserID, d.extionsion) = 0))
        THEN d.Amount ELSE 0 END) AS total_deductions,
        
        MAX(d.Currency) AS deduction_currency
    FROM tblusers u
    LEFT JOIN tblremewal t ON u.lastversion = t.Id
    LEFT JOIN Deductions d ON 1=1
    WHERE u.isemp IS NOT NULL
    GROUP BY u.UserID
),

UserIncentives AS (
    SELECT 
        u.UserID,
        SUM(CASE 
            WHEN (i.for_what = 1 AND FIND_IN_SET(u.UserID, i.IncentiveUserID) > 0)
              OR (i.for_what = 2 AND FIND_IN_SET(t.GroupID, i.IncentiveUserID) > 0 AND (i.extionsion IS NULL OR FIND_IN_SET(u.UserID, i.extionsion) = 0))
              OR (i.for_what = 3 AND FIND_IN_SET(t.GradeID, i.IncentiveUserID) > 0 AND (i.extionsion IS NULL OR FIND_IN_SET(u.UserID, i.extionsion) = 0))
              OR (i.for_what = 4 AND FIND_IN_SET(t.SectionID, i.IncentiveUserID) > 0 AND (i.extionsion IS NULL OR FIND_IN_SET(u.UserID, i.extionsion) = 0))
              OR (i.for_what = 5 AND FIND_IN_SET(t.jobtitleID, i.IncentiveUserID) > 0 AND (i.extionsion IS NULL OR FIND_IN_SET(u.UserID, i.extionsion) = 0))
            THEN 
                CASE 
                    WHEN i.AmountType = 'amount' THEN i.Amount
                    WHEN i.AmountType = 'avg' THEN (
                        SELECT 
                            SUM(
                                ps.period_salary * i.Amount / 100 * 
                                DATEDIFF(
                                    LEAST(i.DueDateee, ps.period_end),
                                    GREATEST('$date_range[0]', ps.period_start)
                                ) / DATEDIFF('$date_range[1]', '$date_range[0]')
                            )
                        FROM PeriodSalary ps 
                        WHERE ps.UserID = u.UserID
                          AND i.DueDateee BETWEEN ps.period_start AND ps.period_end
                    )
                    ELSE 0
                END
            ELSE 0 
        END) AS total_incentives,
        MAX(i.Currency) AS incentive_currency
    FROM tblusers u
    LEFT JOIN tblremewal t ON u.lastversion = t.Id
    LEFT JOIN Incentive i ON 1=1 

    WHERE u.isemp IS NOT NULL
    GROUP BY u.UserID

),

Userbenefits AS (
    SELECT 
        u.UserID,
        SUM(
            CASE 
                WHEN (b.for_what = 1 AND FIND_IN_SET(u.UserID, b.benefitUserID) > 0)
                  OR (b.for_what = 2 AND FIND_IN_SET(t.GroupID, b.benefitUserID) > 0 AND (b.extionsion IS NULL OR FIND_IN_SET(u.UserID, b.extionsion) = 0))
                  OR (b.for_what = 3 AND FIND_IN_SET(t.GradeID, b.benefitUserID) > 0 AND (b.extionsion IS NULL OR FIND_IN_SET(u.UserID, b.extionsion) = 0))
                  OR (b.for_what = 4 AND FIND_IN_SET(t.SectionID, b.benefitUserID) > 0 AND (b.extionsion IS NULL OR FIND_IN_SET(u.UserID, b.extionsion) = 0))
                  OR (b.for_what = 5 AND FIND_IN_SET(t.jobtitleID, b.benefitUserID) > 0 AND (b.extionsion IS NULL OR FIND_IN_SET(u.UserID, b.extionsion) = 0))
                THEN 
                    CASE 
                        WHEN b.AmountType = 'amount' THEN b.Amount
                        WHEN b.AmountType = 'avg' THEN (
                            SELECT 
                                SUM(
                                    ps.period_salary * b.Amount / 100 * 
                                    DATEDIFF(
                                        LEAST(b.DueDateee, ps.period_end),
                                        GREATEST('$date_range[0]', ps.period_start)
                                    ) / DATEDIFF('$date_range[1]', '$date_range[0]')
                                )
                            FROM PeriodSalary ps 
                            WHERE ps.UserID = u.UserID
                              AND b.DueDateee BETWEEN ps.period_start AND ps.period_end
                        )
                        ELSE 0
                    END
                ELSE 0 
            END
        ) AS total_benefits,
        MAX(b.Currency) AS benefits_currency
    FROM tblusers u
    LEFT JOIN tblremewal t ON u.lastversion = t.Id
    LEFT JOIN benefit b ON 1=1
    WHERE u.isemp IS NOT NULL 
    GROUP BY u.UserID
),

TotalSalary AS (
    SELECT 
        UserID,period_salary,
        SUM(period_salary) AS total_salary
    FROM PeriodSalary
    GROUP BY UserID
)

SELECT DISTINCT
    u.UserID,
    u.lastversion,
    CONCAT(u.FirstName, ' ', u.LastName) AS person_name, 
    r.Id AS renewal_id,
    r.BranchID,
    b.branch_name,
    ts.total_salary AS Salary,
    wh.totalhour,
    -- wh.Date,
    
    SEC_TO_TIME(COALESCE(wh.total_work_seconds, 0)) AS total_work_hours,
    -- wh.actual_work_days,
    wh.days_in_period,
    
    COALESCE(a.total_advances, 0) AS total_advances,
       COALESCE(rb.remain_salary, 0) AS remain_salary,
    COALESCE(a.advance_currency, '-') AS advance_currency,
    
    COALESCE(d.total_deductions, 0) AS total_deductions,
    COALESCE(d.deduction_currency, '-') AS deduction_currency,

    COALESCE(i.total_incentives, 0) AS total_incentives,
    COALESCE(i.incentive_currency, '-') AS incentive_currency,

    COALESCE(bb.total_benefits, 0) AS total_benefits,
    COALESCE(bb.benefits_currency, '-') AS benefits_currency,
    
    (ts.total_salary + COALESCE(i.total_incentives, 0) + COALESCE(bb.total_benefits, 0) - 
    COALESCE(a.total_advances, 0) - COALESCE(d.total_deductions, 0)) AS net_salary,

    -- عرض تفاصيل فترات الراتب للموظف


    -- عرض تفاصيل فترات الراتب
    -- (SELECT GROUP_CONCAT(sp.totalhour SEPARATOR '; ')
    --  FROM alltotalhour sp WHERE sp.UserID = u.UserID) AS salary_periods_details

(
  SELECT GROUP_CONCAT(
    CONCAT(DATE_FORMAT(sp.Date, '%Y-%m-%d'), ': ', sp.totalhour) 
    SEPARATOR '; ')
  FROM alltotalhour sp 
  WHERE sp.UserID = u.UserID 
  AND sp.Date BETWEEN '$date_range[0]' AND '$date_range[1]'
) AS salary_periods_details



FROM tblusers u
JOIN tblremewal r ON u.lastversion = r.Id
LEFT JOIN branches b ON b.branch_id = r.BranchID
-- LEFT JOIN alltotalhour ath ON ath.UserID = u.UserID
LEFT JOIN EmployeeWorkHours wh ON wh.UserID = u.UserID
LEFT JOIN Advances a ON a.UserID = u.UserID
LEFT JOIN UserDeductions d ON d.UserID = u.UserID
LEFT JOIN remain_salary_tb rb ON rb.UserID = u.UserID
LEFT JOIN UserIncentives i ON i.UserID = u.UserID
LEFT JOIN Userbenefits bb ON bb.UserID = u.UserID
LEFT JOIN TotalSalary ts ON ts.UserID = u.UserID

WHERE 
    u.isemp IS NOT NULL and resigned_or_dismissed is null 
    -- and '$date_range[0]' between r.new_s_date and r.new_e_date   
    and '$date_range[1]' between r.new_s_date and r.new_e_date  
    AND r.BranchID IN ($allowed_branch)
    AND r.state IS NOT NULL
";

    // if(!$User->is_owner){

	// 	$query .= "  and u.BranchID in (" . $User->branches. ") ";

	// } 

    if($_POST["is_date_search"] == "yes")
    {
        $display_search_note = true; 
    
        if(!empty($_POST["branchs"])){
            $in = "";
            $i = 0;
            foreach($_POST["branchs"] as $item)
            {
                    $key =":branchs".$i++;
                   $in .="$key,";
                    $in_params[$key] = $item;
                    $results_note['selected_branch'] []= "$item";
            }
            $in = rtrim($in,",");
            $query .= "  AND  r.BranchID IN ($in)  ";
            $parma = array_merge($parma,$in_params);        
        }	
    
        if(!empty($_POST["section"])){
            $in = "";
            $i = 0;
            foreach($_POST["section"] as $item)
            {
                    $key =":section".$i++;
                   $in .="$key,";
                    $in_params[$key] = $item;
                    $results_note['selected_section'] []= "$item";
            }
            $in = rtrim($in,",");
            $query .= "  AND  r.SectionID IN ($in)  ";
            $parma = array_merge($parma,$in_params);        
        }
        
        if(!empty($_POST["jobtitle"])){
            $in = "";
            $i = 0;
            foreach($_POST["jobtitle"] as $item)
            {
                    $key =":jobtitle".$i++;
                   $in .="$key,";
                    $in_params[$key] = $item;
                    $results_note['selected_jobtitle'] []= "$item";
            }
            $in = rtrim($in,",");
            $query .= "  AND  r.jobtitleID IN ($in)  ";
            $parma = array_merge($parma,$in_params);        
        }
        
        if(!empty($_POST["grade"])){
            $in = "";
            $i = 0;
            foreach($_POST["grade"] as $item)
            {
                    $key =":grade".$i++;
                   $in .="$key,";
                    $in_params[$key] = $item;
                    $results_note['selected_degree'] []= "$item";
            }
            $in = rtrim($in,",");
            $query .= "  AND  r.GradeID IN ($in)  ";
            $parma = array_merge($parma,$in_params);        
        }

        if(!empty($_POST["shift"])){
            $in = "";
            $i = 0;
            foreach($_POST["shift"] as $item)
            {
                    $key =":shift".$i++;
                   $in .="$key,";
                    $in_params[$key] = $item;
                    $results_note['selected_period'] []= "$item";
            }
            $in = rtrim($in,",");
            $query .= "  AND  t.shiftID IN ($in)  ";
            $parma = array_merge($parma,$in_params);        
        }

        if(!empty($_POST["groub"])){
            $in = "";
            $i = 0;
            foreach($_POST["groub"] as $item)
            {
                    $key =":groub".$i++;
                   $in .="$key,";
                    $in_params[$key] = $item;
                    $results_note['selected_group'] []= "$item";
            }
            $in = rtrim($in,",");
            $query .= "  AND  r.GroupID IN ($in)  ";
            $parma = array_merge($parma,$in_params);        
        }
        
        if(!empty($_POST["name"])){
            $query .= " AND (u.FirstName LIKE '%" . $_POST["name"] . "%' OR u.LastName LIKE '%" . $_POST["name"] . "%')";
        $results_note ['name'] = true; 
        } 
        
        
    }

    $query.=' order by r.UserID Asc ';

$query1 = '';

if($_POST["length"] != -1)
{
 $query1 = 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
}

$statement = $connect_pdo->prepare($query);

$statement->execute($parma);

$number_filter_row = $statement->rowCount();

$statement = $connect_pdo->prepare($query . $query1);

$statement->execute($parma);

$result = $statement->fetchAll();

$data = array();
$filtered_group ='';
// تحول الوقت الى عدد عشري
function timeToDecimalHours($time) {
    list($hours, $minutes, $seconds) = explode(':', $time);
    return $hours + ($minutes / 60) + ($seconds / 3600);
}
// للحصول على الشهر والسنه
list($day, $month, $year) = explode('-', $date_range[0]);
$days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
foreach($result as $row)
{
$total_credited_minutes = 0;
if (!empty($row['total_work_hours']) && $row['total_work_hours'] != '-' && $row['total_work_hours'] != '00:00') {
    list($hours, $minutes) = explode(':', $row['total_work_hours']);
    $total_credited_minutes += ($hours * 60) + $minutes;
}
$total_hours = floor($total_credited_minutes / 60);
$total_minutes = $total_credited_minutes % 60;
$total_credited_time = sprintf('%02d:%02d', $total_hours, $total_minutes);

//  الراتب بالساعه كالتالي
    $work_hours_per_day = timeToDecimalHours($row['totalhour']); // مثلاً "08:00:00" => 8.0
    $monthly_work_hours = $work_hours_per_day * $row['days_in_period'];
    $salary = floatval(str_replace(',', '', $row['Salary'])); // تأكد من أن الراتب رقم
    $salary_per_hour = $monthly_work_hours > 0 ? $salary / $monthly_work_hours : 0;
    $worked_hours = $total_credited_minutes / 60;
    $earned_salary = round($salary_per_hour * $worked_hours, 2);

// الحسابات
//  $earned_salary = round($salary_per_hour * $worked_hours, 2);// اجمالي الراتب بالنسبة لعدد ساعات المعل
 $remain_salary=!empty($row['remain_salary'])?$row['remain_salary']:0;
 $total_advances=!empty($row['total_advances'])?$row['total_advances']:0;// أجمالي سلف الموظف
 $total_deductions=!empty($row['total_deductions'])?$row['total_deductions']:0;// أجمالي خصم الموظف
 $total_benefits=!empty($row['total_benefits'])?$row['total_benefits']:0;// أجمالي المكافاّت الموظف
 $total_incentives=!empty($row['total_incentives'])?$row['total_incentives']:0;// أجمالي التعويضات الموظف
 // الراتب النهائي
 $end_salary=$earned_salary+$total_benefits+$total_incentives-$total_advances-$remain_salary-$total_deductions;
    // benefits_currency total_benefits
 $sub_array = array();
 $sub_array[] = $row['person_name'];
 $sub_array[] = $row['branch_name'];
 $sub_array[] = number_format($row['Salary'],2);
//  $sub_array[] = ($row['salary_periods_details']);
//  
 $sub_array[] =  !empty($row['remain_salary']) ? ('-(' . number_format( $row['remain_salary'],2) . ') SAR') : '(0) SAR';
 $sub_array[] = !empty($row['total_advances']) ? ('-(' . number_format( $row['total_advances'],2) . ')' . $row['advance_currency']) : '(0)' . $row['advance_currency'];
 $sub_array[] = !empty($row['total_deductions']) ? ('-(' .number_format(  $row['total_deductions'],2) . ')' . $row['deduction_currency']) : '(0)' . $row['deduction_currency'];
 $sub_array[] =  !empty($row['total_benefits']) ? ('(' . number_format( $row['total_benefits'],2). ')' . $row['benefits_currency']) : '(0)' . $row['benefits_currency'];
 $sub_array[] = !empty($row['total_incentives']) ? ('(' .number_format( $row['total_incentives'],2) . ')' . $row['incentive_currency']) : '(0)' . $row['incentive_currency'];

 
 $sub_array[] = $total_credited_time;
  $sub_array[] =number_format($end_salary,2);
  $sub_array[] = ($row['salary_periods_details']);
//   $sub_array[] = $row['Salary'];
 $data[] = $sub_array;
}



$output = array(
 'draw'   => intval($_POST['draw']),
 'data'   => $data,
"results_note" => $results_note
);

echo json_encode($output);


//}

?>