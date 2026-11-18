"use client";

import PortalShell from "@/components/PortalShell";
import ResourcePanel from "@/components/ResourcePanel";

export default function FacultySchedulePage() {
  return (
    <PortalShell title="Master Schedule" role="faculty" links={[
      { href: "/faculty", label: "Overview" }, { href: "/faculty/schedule", label: "Schedules" },
      { href: "/faculty/rooms", label: "Rooms" },
    ]}>
      <ResourcePanel resource="schedules" title="All Schedules" columns={["schedule_id", "prof_user_id", "subject_id", "section_id", "room_id", "schedule_day", "schedule_time"]} fields={["prof_user_id", "subject_id", "section_id", "room_id", "schedule_time", "schedule_end_time", "schedule_day"]} />
    </PortalShell>
  );
}
