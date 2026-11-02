# The Full Project Description

## Fair Distribution & Benefit Ledger Platform

**Internal name:** fair-benefit-ledger · **Public platform name:** [Not yet determined or selected] · **Status:** Idea and design stage, early version for discussion

### 1. Project Summary

A software platform (SaaS) that helps organizations offering limited opportunities or resources to a large number of applicants do two things: find out who has already benefited, and decide who should come first now, based on rules and criteria that each organization sets for itself.

The platform does most of the work automatically: importing and cleaning lists, matching people, applying eligibility rules, calculating priority, ranking, and preparing reports. The final decision stays with a human for doubtful cases and at approval.

The platform works in Arabic and English, and the people registered in it can have names in any language. It starts with incubators and student programs, then expands to other sectors.

### 2. Vision and Goals

**Vision:** That limited opportunities and resources reach those who deserve them, through documented decisions that can be explained, with as little manual effort as possible.

- Prevent repeated benefit and give people who have not benefited before a chance.
- Rank applicants by clear criteria that each organization defines.
- Reduce manual review time and limit human involvement to the cases that need it.
- Provide transparency and reports that can be shown to funders and applicants.
- Work in two languages and with very large files.

### 3. The Problem

An organization receives large lists of applicants and has no fast, reliable way to know who has already benefited from its previous programs.

The result: the same people benefit again and again, while others never get the opportunity.

Manual checking across Excel files is slow and error-prone, especially when names are written differently (such as محمد / محمّد / Mohammad), when name order differs, and with spelling errors.

Priority criteria are often undocumented, so it is hard to explain to applicants, funders, or oversight bodies why one person was accepted and another declined.

### 4. The Solution: Three Layers in One System

- **Benefit Ledger:** a ledger private to each organization and isolated from others. It answers: who benefited? from which program? how many times? and when? It treats withdrawals fairly.
- **Eligibility & Priority Engine:** applies the organization’s conditions (waiting periods, no overlap, maximum), calculates scores, ranks applicants, cuts at the available quantity, and creates the accepted and waiting lists.
- **Reports & Transparency:** export-ready lists, reports showing coverage and distribution, the reason behind each case’s ranking, and an audit trail for every decision.

### 5. Target Users and Rollout Phases

| Phase | Sector | Notes |
|---|---|---|
| 1 | Incubators and student-project programs | Starting point: lower risk than relief, so automation can be higher |
| 2 | Courses and training, student grants and aid, subsidized health services | The same engine with different rules and terminology |
| 3 | Relief and aid | More sensitive data: human approval is the default |
| Later | Restaurant and retail offers and loyalty programs | One per customer |

### 6. How the Platform Works

1. The organization uploads past program lists, then the new applicant list (Excel or CSV).
2. The system maps the columns and cleans the data automatically.
3. It matches people by identifier and name, with a confidence score for each match.
4. It applies eligibility rules (simultaneous participation, waiting period, completed only).
5. It calculates priority scores and ranks applicants, with quotas if any.
6. It cuts the list at the available quantity and creates a waiting list.
7. The organization reviews only the doubtful cases and those near the cut-off line, then approves, rejects, or grants an exception with the reason recorded.
8. The system updates the cumulative ledger and exports the lists and reports.

### 7. Core Concepts

- **Organization:** the entity using the platform. It has its own data and rules, isolated from others.
- **Person:** an individual in the organization’s ledger, with a primary identifier and supporting data.
- **Program:** a limited opportunity or resource: a project, a course, a grant, aid.
- **Participation:** a person’s link to a program, with a status (registered, accepted, active, completed, withdrawn) and start and end dates.
- **Cycle:** one distribution run: the applicant list, the available quantity, the rules applied, and the results.
- **Rule:** an eligibility condition or priority criterion, with an archived version.
- **Decision:** the outcome of each case: accepted, waiting list, rejected, or exception, with the reason.

### 8. Eligibility Rules

Every rule can be configured per organization. The agreed core rules:

- **No simultaneous participation:** the maximum number of active projects per person = 1.
- **Waiting period:** a person cannot be accepted into a new project until a set period (default 12 months) has passed since the end date of their previous project. Eligibility is evaluated at the start date of the new project.
- **Withdrawals do not count:** someone who registered and withdrew, or did not complete, is not counted as a real beneficiary, so the waiting period does not apply. Their participation stays visible in the ledger.

Edge cases and how they are handled:

- **Applying to several programs in the same cycle:** the person gets the program where they rank or prefer highest, is then removed from the others, and the next person on the waiting list is moved up automatically (cascading allocation).
- **Team projects:** the rules apply to each member. When a member is ineligible, the default is that the system flags it and asks for a human decision (replace the member, postpone, or grant an exception).
- **Someone accepted who then withdraws:** the seat is reopened and the next person on the waiting list is moved up.
- **Exceptions:** recorded with the reason, who approved them, and when.

### 9. Priority and Ranking Engine

The engine works in a fixed sequence:

1. **Hard filters (eligibility):** exclude those who are not eligible.
2. **Weighted scoring:** each criterion has a weight that the organization defines.
3. **Quotas (optional):** such as a minimum share for a particular group or a cap per university.
4. **Tie-breakers:** in a clear order (such as the longest wait since the last benefit or the earliest registration).
5. **Cut-off at the available quantity:** the rest go to an ordered waiting list.

Its properties:

- Each organization defines its own criteria, with ready-made templates (incubator, grants, courses) that can be edited.
- Automatic rule validation before running: illogical weights, conflicting conditions, a criterion with no data.
- A “what if” simulation before approval.
- Every result is tied to the rule version that was applied at the time.
- Judges’ scores: imported, normalized (since judges differ in strictness), and combined automatically into the scores. The system does not judge the quality of an idea.
- Missing data: marked “unverified” and handled according to the organization’s policy, not treated as zero automatically.
- Decisions are deterministic: the same input gives the same result. Artificial intelligence is for suggestions only (column mapping, suggesting similar names, cleaning text) and never settles eligibility or ranking.

### 10. Identity and Name Matching

- **Primary identifier:** national ID, passport, or student number. The identifier type and format are configurable per organization.
- **Supporting identifiers:** name, date of birth, and phone.
- **Names in any language:** Arabic, Latin, and mixed, with differences in spelling and transliteration between scripts (such as محمد ↔ Mohammad / Mohamed), name order, diacritics, and spelling errors.
- **Confidence bands:** high confidence (such as a matching ID) is accepted automatically. Medium confidence (a similar name with no ID) is shown for review with the reasons. Low confidence is treated as a different person. The thresholds are configurable.
- **Performance:** records are not compared all against all. Blocking keys and approximate-search indexes are used.
- **Protection against error:** a wrong match can deprive someone, so the system flags and humans decide in medium-confidence cases.

### 11. Automation and the Human Role

Zero human involvement is not required. The principle: automate the routine, and send anything doubtful to a person. Each organization chooses its automation level:

| Level | How it works | Suited to |
|---|---|---|
| Full automation | The system ranks, approves, and updates the ledger | Low-risk decisions |
| Automation + borderline review (default for incubators) | Automatic for everything, with a person reviewing only the cases near the cut-off line and the doubtful ones | Incubators and student programs |
| Manual approval (default for relief) | The system suggests and humans approve | Sensitive cases |

Safeguards at every level:

- A full audit trail for every decision and edit.
- Circuit breaker: if a file has an abnormal rate of duplicates or unexpected columns, the system stops processing and alerts.
- The ability to roll back a whole batch.
- Automatic alerts: limit exceeded, weak coverage, undocumented receipt.
- An appeal path that re-evaluates the case.

### 12. Reports and Transparency

- **Ranking reason:** each case shows its score broken down by criterion.
- **Audit trail:** who edited, approved, or granted an exception, and when. This is a different concept from the ranking reason.
- The final distribution lists, the waiting list, and the exceptions report, in Excel and PDF.
- Coverage and fairness reports: coverage per site or area, who has not yet been reached, and who has benefited most often.
- Scheduled periodic reports for funders.

### 13. Bilingual Requirements

- A full interface in Arabic and English, with direction switching (right-to-left and left-to-right), and a separate language setting for each user.
- Export of reports and lists in either language.
- Rule templates and field names in both.
- Reading files whose column headers are in Arabic, English, or a mix of both (اسم / Name).
- Numbers in both forms (٠١٢ and 012), and dates in different formats.
- Names in mixed scripts within the same file.
- Several identifier types, with formats that vary by country.

### 14. Data Scale and Performance

The design assumes the largest scenario: very large files. The figures below are design assumptions to be tested with an early performance test, not promises.

- Excel is limited to about 1,048,576 rows, so CSV is supported for larger sizes.
- Background processing: chunked upload, streaming reads, work queues, a progress indicator, and the ability to resume.
- Design assumption: a ledger of tens of millions of records per organization, and a new file of around one million rows processed within minutes.
- Start with a simple, scalable architecture (queues and a relational database), without complex distributed systems before demand is proven.

### 15. Privacy and Security

- Each organization’s data is fully isolated and belongs to that organization.
- No public registry of people, and no collection of data beyond what the organization uploads.
- No comparison between different organizations without explicit consent from both sides, at a later stage, and on hashed data.
- Minimum data, especially sensitive data (disability, social or health status): collected only when necessary and with restricted access.
- Roles and permissions (administrator, reviewer, data entry, view only), data encryption, and a retention and deletion policy.
- Compliance with data protection laws in the target markets (a decision that is still open).
- The system suggests and ranks; the final decision stays with the organization.

### 16. Business Model

Pricing will be discussed in detail later. The directions below are for discussion only and are not decisions:

- A limited free tier (one organization and a limited number of records).
- Paid tiers based on active records, number of programs, and users.
- Reduced or free pricing for nonprofits, and full commercial pricing for the commercial sector.
- Added services: data cleaning, training, and custom reports.

### 17. MVP Scope

**In scope:**

- Excel / CSV import with column mapping and cleaning.
- Matching by identifier and name (Arabic / English) with a confidence score.
- Eligibility rules: the active-project limit, the waiting period, and completed only.
- Simple priority: 4 to 6 weighted criteria, with the available quantity and a waiting list.
- A review screen: approve, reject, and exception with the reason.
- Updating the cumulative ledger after approval.
- Exporting the lists and the report.
- An Arabic / English interface, basic roles, and an audit trail.

**Out of scope (later):**

- Comparison between different organizations.
- A field app that works without internet.
- Integrations (WhatsApp and Google Sheets) and an API.
- Complex nested rules and machine learning in ranking.
- Billing and subscriptions.
- Dedicated relief modules.

### 18. Pilot Plan and Success Metrics

A pilot with one or two organizations: a past list and a new list (a sample or anonymized data), then comparing the result with their current method. Target figures are set with the pilot organization. The metrics:

- Time saved in screening compared with the manual method.
- The number of repeat cases the system found that were not found manually.
- The share of cases that needed human review.
- Matching accuracy (wrong and missed matches), checked by the organization’s team.
- Team satisfaction, and how clear the reports are for funders.

### 19. Risks and Mitigations

- Sensitive data and the risk of a leak → isolation, encryption, minimum data, permissions, and an audit trail.
- A wrong match that deprives a person → confidence bands, human review of medium cases, showing the reasons, and the ability to roll back.
- Poor data quality → automatic cleaning, an “unverified” label, and a file quality report before processing.
- Rules configured incorrectly → templates, automatic validation, simulation before approval, and archived versions.
- Spreading across sectors → start with one sector, then expand once the result is proven.
- Cost and complexity of large volumes → a simple, scalable architecture and an early performance test.
- Data laws that differ between countries → choosing launch markets early, and getting legal advice.

### 20. Open Decisions

- The platform name.
- The launch markets, the data protection laws, and where the data is hosted.
- Pricing.
- The policy for teams when a member is ineligible.
- The first pilot organization and its rules and criteria.
- Whether the default waiting period differs between sectors (the current default is 12 months for incubators).
