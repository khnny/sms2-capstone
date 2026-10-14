<?php
/**
 * Fictional, read-only communication prototype records.
 *
 * Keep these records separate from production announcements and CRAD workflows.
 */
declare(strict_types=1);

function smsCommunicationDemoAnnouncements(): array
{
    return [
        [
            'id' => 'ann-1',
            'title' => 'Research proposal defense schedule released',
            'category' => 'Defense',
            'published_at' => '2026-09-28',
            'audience' => 'Research students',
            'status' => 'Published',
            'priority' => 'Important',
            'description' => 'The October proposal defense lineup is available. Please review your assigned time and panel details.',
            'author' => 'CRAD Research Office',
        ],
        [
            'id' => 'ann-2',
            'title' => 'Final manuscript submission deadline',
            'category' => 'Deadline',
            'published_at' => '2026-09-27',
            'audience' => 'Thesis groups',
            'status' => 'Published',
            'priority' => 'Important',
            'description' => 'Final manuscripts for the October defense cycle must be submitted by October 18 at 5:00 PM.',
            'author' => 'Research Coordination Desk',
        ],
        [
            'id' => 'ann-3',
            'title' => 'Research consultation hours this week',
            'category' => 'Research',
            'published_at' => '2026-09-26',
            'audience' => 'Research students',
            'status' => 'Published',
            'priority' => 'Normal',
            'description' => 'Advisers will hold consultation hours on September 30 in the Research Hub and via the campus meeting rooms.',
            'author' => 'College of Computer Studies',
        ],
        [
            'id' => 'ann-4',
            'title' => 'Panel evaluation rubric updated',
            'category' => 'Research',
            'published_at' => '2026-09-24',
            'audience' => 'Faculty & panel',
            'status' => 'Published',
            'priority' => 'Normal',
            'description' => 'The sample rubric now includes a section for research ethics and data stewardship.',
            'author' => 'CRAD Research Office',
        ],
        [
            'id' => 'ann-5',
            'title' => 'Research presentation rehearsal sign-up',
            'category' => 'General',
            'published_at' => '2026-09-22',
            'audience' => 'Research students',
            'status' => 'Draft',
            'priority' => 'Normal',
            'description' => 'A draft notice for presentation rehearsal slots in the Research Hub.',
            'author' => 'Student Research Council',
        ],
        [
            'id' => 'ann-6',
            'title' => 'September research forum recap',
            'category' => 'General',
            'published_at' => '2026-09-18',
            'audience' => 'Everyone',
            'status' => 'Archived',
            'priority' => 'Normal',
            'description' => 'Highlights and shared resources from the September research forum.',
            'author' => 'CRAD Research Office',
        ],
        [
            'id' => 'ann-7',
            'title' => 'Title submission window closes October 2',
            'category' => 'Deadline',
            'published_at' => '2026-09-29',
            'audience' => 'Research students',
            'status' => 'Published',
            'priority' => 'Urgent',
            'description' => 'Submit title packets before 5:00 PM on October 2 to be included in the current review cycle.',
            'author' => 'Research Coordination Desk',
        ],
        [
            'id' => 'ann-8',
            'title' => 'Old orientation room notice',
            'category' => 'General',
            'published_at' => '2026-09-15',
            'audience' => 'Faculty & panel',
            'status' => 'Unpublished',
            'priority' => 'Normal',
            'description' => 'Previous room assignment notice retained as an example of an unpublished record.',
            'author' => 'CRAD Research Office',
        ],
        [
            'id' => 'ann-9',
            'title' => 'Research office consultation hours',
            'category' => 'General',
            'published_at' => '2026-09-25',
            'audience' => 'Everyone',
            'status' => 'Published',
            'priority' => 'Normal',
            'description' => 'The research office is available for general process questions every Wednesday from 9:00 AM to 12:00 PM.',
            'author' => 'CRAD Research Office',
        ],
    ];
}
