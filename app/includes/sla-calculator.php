<?php
function calculateSLA($link, $requestId, $dateCreated, $dateResolved = null) {
    // Use the current date if no end date is provided
    $endDate = $dateResolved ? $dateResolved : date('Y-m-d');

    // Query to get statuses and timestamps for the request, ensuring we only consider statuses within the date range
    $query = "SELECT statusID, DATE(ChangeTimestamp) AS ChangeDate 
              FROM StatusHistory 
              WHERE requestID = '$requestId' 
              AND DATE(ChangeTimestamp) <= '$endDate' 
              ORDER BY ChangeDate";

    $result = mysqli_query($link, $query);

    // Array for debugging calculations
    $calculationSteps = [];
    $totalCountableDays = 0;

    // If no status history exists, assume default status and calculate directly
    if (mysqli_num_rows($result) == 0) {
        return shouldCountStatus(1) ? calculateBusinessDays($dateCreated, $endDate, $link) : 0;
    }

    // Initialize the first status
    $previousStatus = 1;
    $previousChangeDate = $dateCreated;

    // Process each status change
    while ($row = mysqli_fetch_assoc($result)) {
        $nextStatus = $row['statusID'];
        $nextChangeDate = $row['ChangeDate'];
        
        // Ensure we do not process status changes beyond `dateResolved`
        if ($nextChangeDate > $endDate) {
            break;
        }

        if($dateCreated > $nextChangeDate) continue;

        // Calculate business days between previous change date and next change date
        $currentBusinessDays = calculateBusinessDays($previousChangeDate, $nextChangeDate, $link);

        // Only count business days for statuses that should be counted
        if (shouldCountStatus($previousStatus)) {
            $totalCountableDays += $currentBusinessDays;
        }

        // Store debugging info
        $calculationSteps[] = [
            "fromDate" => $previousChangeDate,
            "toDate" => $nextChangeDate,
            "fromStatus" => $previousStatus,
            "toStatus" => $nextStatus,
            "businessDaysBetween" => $currentBusinessDays,
            "accumulatedTotal" => $totalCountableDays,
            "counts" => shouldCountStatus($previousStatus)
        ];

        // Update previous values
        $previousStatus = $nextStatus;
        $previousChangeDate = $nextChangeDate;
    }

    // Ensure last interval is respected within `dateResolved`
    if ($previousChangeDate < $endDate) {
        $currentBusinessDays = calculateBusinessDays($previousChangeDate, $endDate, $link);
        if (shouldCountStatus($previousStatus)) {
            $totalCountableDays += $currentBusinessDays;
        }
        $calculationSteps[] = [
            "fromDate" => $previousChangeDate,
            "toDate" => $endDate,
            "fromStatus" => $previousStatus,
            "toStatus" => "resolved",
            "businessDaysBetween" => $currentBusinessDays,
            "accumulatedTotal" => $totalCountableDays,
            "counts" => shouldCountStatus($previousStatus)
        ];
    }

    return max(0, $totalCountableDays); // Ensuring the total is non-negative
}


function shouldCountStatus($status) {
    $excludedStatuses = [2, 4, 5, 11, 12];
    return !in_array($status, $excludedStatuses);
}

function calculateBusinessDays($startDate, $endDate, $link = null) {
    $businessDays = 0;
    $currentDate = $startDate;

    while ($currentDate <= $endDate) {
        if (isBusinessDay($currentDate, $link)) {
            $businessDays++;
        }
        $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
    }

    return $businessDays;
}



function isBusinessDay($date, $link = null) {
    $dayOfWeek = date('N', strtotime($date));
    
    // If not a weekday, return false immediately
    if ($dayOfWeek >= 6) {
        return false;
    }
    
    // If database connection provided, check holidays from database
    if ($link !== null) {
        $safeDate = mysqli_real_escape_string($link, $date);
        $query = "SELECT COUNT(*) as count FROM tblholidays WHERE holiday_date = '$safeDate' AND status = 1";
        $result = mysqli_query($link, $query);
        
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            return $row['count'] == 0; // True if not a holiday
        }
    }
    
    // Fallback: If no database connection, use hardcoded holidays (for backward compatibility)
    $holidays = [
        '2019-01-01', '2019-04-19', '2019-04-23', '2019-05-20', '2019-07-01', 
        '2019-08-05', '2019-09-02', '2019-10-14', '2019-11-11', '2019-12-25', 
        '2019-12-26', '2020-01-01', '2020-04-10', '2020-04-14', '2020-05-18', 
        '2020-07-01', '2020-08-03', '2020-09-07', '2020-10-12', '2020-11-11', 
        '2020-12-25', '2020-12-30', '2021-01-01', '2021-04-02', '2021-04-06', 
        '2021-05-24', '2021-07-01', '2021-08-02', '2021-09-06', '2021-09-30', 
        '2021-10-11', '2021-11-11', '2021-12-28', '2021-12-29', '2022-01-05', 
        '2022-04-15', '2022-04-19', '2022-05-23', '2022-07-01', '2022-08-01', 
        '2022-09-05', '2022-09-30', '2022-10-10', '2022-11-11', '2022-12-26', 
        '2022-12-27', '2023-01-03', '2023-04-07', '2023-04-11', '2023-05-22', 
        '2023-07-05', '2023-08-07', '2023-09-04', '2023-10-04', '2023-10-09', 
        '2023-11-15', '2023-12-25', '2023-12-26', '2024-01-01', '2024-03-29', 
        '2024-04-02', '2024-05-20', '2024-07-01', '2024-08-05', '2024-09-02', 
        '2024-09-30', '2024-10-14', '2024-11-11', '2024-12-25', '2024-12-26', 
        '2025-01-01', '2025-04-18', '2025-04-22', '2025-05-19', '2025-07-01', 
        '2025-08-04', '2025-09-01', '2025-09-30', '2025-10-13', '2025-11-11', 
        '2025-12-25', '2025-12-26', '2026-01-01', '2026-04-03', '2026-04-07', 
        '2026-05-18', '2026-07-01', '2026-08-03', '2026-09-07', '2026-09-30', 
        '2026-10-12', '2026-11-11', '2026-12-25', '2026-12-30', '2027-01-01', 
        '2027-03-26', '2027-03-30', '2027-05-24', '2027-07-01', '2027-08-02', 
        '2027-09-06', '2027-09-30', '2027-10-11', '2027-11-11', '2027-12-28', 
        '2027-12-29', '2028-01-05', '2028-04-14', '2028-04-18', '2028-05-22', 
        '2028-07-05', '2028-08-07', '2028-09-04', '2028-10-04', '2028-10-09', 
        '2028-11-15', '2028-12-25', '2028-12-26', '2029-01-01', '2029-03-30', 
        '2029-04-03', '2029-05-21', '2029-07-03', '2029-08-06', '2029-09-03', 
        '2029-10-02', '2029-10-08', '2029-11-13', '2029-12-25', '2029-12-26', 
        '2030-01-01', '2030-04-19', '2030-04-23', '2030-05-20', '2030-07-01', 
        '2030-08-05', '2030-09-02', '2030-09-30', '2030-10-14', '2030-11-11', 
        '2030-12-25', '2030-12-26'
    ];
    
    return !in_array($date, $holidays);
}function calculateStatusAvg($link, $startDate, $endDate, $strCat) {
    // Define the desired status order (excluding "Resolved")
    $preferredOrder = [
        "Information Gathering",
        "Unassigned",
        "Assigned",
        "Reassigned",
        "Escalated",
        "In progress",
        "Return to client",
        "On hold"
    ];

    // Initialize cumulative data for each preferred status
    $statusData = array_fill_keys($preferredOrder, ["totalDays" => 0, "occurrences" => 0]);

    // Overall accumulators
    $totalDaysForAllStatuses = 0;
    $totalStatusTransitions = 0;
    $totalRequests = 0;
    $requestDetails = [];

    if (!empty($strCat)) {
        $strCat = " AND $strCat";
    }

    $query = "
        SELECT *
        FROM tbltriage
        WHERE (dateresolved BETWEEN '$startDate' AND '$endDate')
          AND status = '1' 
          $strCat
    ";

    $result = mysqli_query($link, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $totalRequests++;
            $requestId = $row['requestid'];

            $dateReceived = (!empty($row['slatimer']) && $row['slatimer'] != "0000-00-00") ? $row['slatimer'] : $row['datereceived'];
            $dateResolved = $row['dateresolved'];

            // Grab status history for the request, ensuring we only include statuses before `dateResolved`
            $secondQuery = "
                SELECT sh.statusID, s.nameen, DATE(ChangeTimestamp) AS ChangeDate 
                FROM StatusHistory sh 
                JOIN tblstatus s ON sh.statusID = s.id 
                WHERE requestID = '$requestId' 
                AND DATE(ChangeTimestamp) <= '$dateResolved'
                ORDER BY ChangeTimestamp ASC
            ";
            $secondResult = mysqli_query($link, $secondQuery);

            $previousStatusName = "Assigned";
            $previousDate = $dateReceived;
            $totalTimeForRequest = 0;

            if ($secondResult && mysqli_num_rows($secondResult) > 0) {
                while ($secondRow = mysqli_fetch_assoc($secondResult)) {
                    $statusName = $secondRow['nameen'];
                    $changeDate = $secondRow['ChangeDate'];

                    if ($changeDate > $dateResolved) break;

                    $businessDays = calculateBusinessDays($previousDate, $changeDate);

                    if (isset($statusData[$previousStatusName]) && shouldCountStatus($previousStatusName)) {
                        $statusData[$previousStatusName]["totalDays"] += $businessDays;
                        $statusData[$previousStatusName]["occurrences"]++;
                        $totalStatusTransitions++;
                        $totalDaysForAllStatuses += $businessDays;
                        $totalTimeForRequest += $businessDays;
                    }

                    $previousStatusName = $statusName;
                    $previousDate = $changeDate;
                }
            }

            // Final interval from last status to resolution (excluding "Resolved")
            $lastBusinessDays = calculateBusinessDays($previousDate, $dateResolved);
            if (isset($statusData[$previousStatusName]) && shouldCountStatus($previousStatusName) && $previousStatusName != "Resolved") {
                $statusData[$previousStatusName]["totalDays"] += $lastBusinessDays;
                $statusData[$previousStatusName]["occurrences"]++;
                $totalStatusTransitions++;
                $totalDaysForAllStatuses += $lastBusinessDays;
                $totalTimeForRequest += $lastBusinessDays;
            }

            // Store details of this request
            $requestDetails[] = ['requestId' => $requestId, 'totalTime' => $totalTimeForRequest];
        }
    }

    // Calculate averages for each status
    $statusAverages = [];
    foreach ($preferredOrder as $statusName) {
        $data = $statusData[$statusName];
        $statusAverages[$statusName] = [
            "avgDays" => $totalRequests > 0 ? $data["totalDays"] / $totalRequests : 0,
            "avgDaysPerOccurrence" => $data["occurrences"] > 0 ? $data["totalDays"] / $data["occurrences"] : 0,
            "avgOccurrence" => $totalStatusTransitions > 0 ? (100 * $data["occurrences"] / $totalStatusTransitions) : 0,
            "percentageOfTotalDays" => $totalDaysForAllStatuses > 0 ? (100 * $data["totalDays"] / $totalDaysForAllStatuses) : 0
        ];
    }

    // Overall summary
    return [
        "statusAverages" => $statusAverages,
        "overallAverages" => [
            "totalRequests" => $totalRequests,
            "totalStatusTransitions" => $totalStatusTransitions,
            "totalDays" => $totalDaysForAllStatuses,
            "avgOverallDaysPerRequest" => $totalRequests > 0 ? $totalDaysForAllStatuses / $totalRequests : 0
        ],
        "requestDetails" => json_encode($requestDetails)
    ];
}
