SELECT Chart_Changes.Encounter_Number
	,Chart_Changes.Reviewer_Name
	,IIf(Sum(IIf([field] = "Diagnosis_Code", 1, 0)) > 0, "Yes", "") AS MRDx_Changed
	,(
		SELECT TOP 1 New_Value
		FROM Chart_Changes AS TEMP
		WHERE TEMP.Encounter_Number = Chart_Changes.Encounter_Number
			AND TEMP.Reviewer_Name = Chart_Changes.Reviewer_Name
			AND Field = "Diagnosis_Code"
		ORDER BY Date_Of_Change DESC
		) AS New_MRDx
	,IIf(Sum(IIf([field] = "CMG", 1, 0)) > 0, "Yes", "") AS CMG_Changed
	,(
		SELECT TOP 1 CMG_Description
		FROM CMGs
		WHERE CMG = (
				SELECT TOP 1 New_Value
				FROM Chart_Changes AS TEMP
				WHERE TEMP.Encounter_Number = Chart_Changes.Encounter_Number
					AND TEMP.Reviewer_Name = Chart_Changes.Reviewer_Name
					AND Field = "CMG"
				ORDER BY Date_Of_Change DESC
				)
		) AS New_CMG
	,IIf(Sum(IIf([field] = "HIG_Code", 1, 0)) > 0, "Yes", "") AS HIG_Changed
	,(
		SELECT TOP 1 HIG_Description
		FROM HIGs
		WHERE HIG = (
				SELECT TOP 1 New_Value
				FROM Chart_Changes AS TEMP
				WHERE TEMP.Encounter_Number = Chart_Changes.Encounter_Number
					AND TEMP.Reviewer_Name = Chart_Changes.Reviewer_Name
					AND Field = "HIG_Code"
				ORDER BY Date_Of_Change DESC
				)
		) AS New_HIG
	,(
		SELECT TOP 1 New_Value
		FROM Chart_Changes AS TEMP
		WHERE TEMP.Encounter_Number = Chart_Changes.Encounter_Number
			AND TEMP.Reviewer_Name = Chart_Changes.Reviewer_Name
			AND Field = "CMG_ELOS"
		ORDER BY Date_Of_Change DESC
		) AS ELOS
	,(
		SELECT TOP 1 New_Value
		FROM Chart_Changes AS TEMP
		WHERE TEMP.Encounter_Number = Chart_Changes.Encounter_Number
			AND TEMP.Reviewer_Name = Chart_Changes.Reviewer_Name
			AND Field = "RIW"
		ORDER BY Date_Of_Change DESC
		) AS RIW
	,(
		SELECT TOP 1 New_Value
		FROM Chart_Changes AS TEMP
		WHERE TEMP.Encounter_Number = Chart_Changes.Encounter_Number
			AND TEMP.Reviewer_Name = Chart_Changes.Reviewer_Name
			AND Field = "HIG_ELOS"
		ORDER BY Date_Of_Change DESC
		) AS HIG_ELOS
	,(
		SELECT TOP 1 New_Value
		FROM Chart_Changes AS TEMP
		WHERE TEMP.Encounter_Number = Chart_Changes.Encounter_Number
			AND TEMP.Reviewer_Name = Chart_Changes.Reviewer_Name
			AND Field = "HIG_Weight"
		ORDER BY Date_Of_Change DESC
		) AS HIG_Weight
	,(
		SELECT TOP 1 New_Value
		FROM Chart_Changes AS TEMP
		WHERE TEMP.Encounter_Number = Chart_Changes.Encounter_Number
			AND TEMP.Reviewer_Name = Chart_Changes.Reviewer_Name
			AND Field = "HIG_LongStayTrimDays"
		ORDER BY Date_Of_Change DESC
		) AS HIG_LongStayTrimDays
	,(
		SELECT TOP 1 New_Value
		FROM Chart_Changes AS TEMP
		WHERE TEMP.Encounter_Number = Chart_Changes.Encounter_Number
			AND TEMP.Reviewer_Name = Chart_Changes.Reviewer_Name
			AND Field = "Comments"
		ORDER BY Date_Of_Change DESC
		) AS Comments
FROM Chart_Changes
GROUP BY Chart_Changes.Encounter_Number
	,Chart_Changes.Reviewer_Name
