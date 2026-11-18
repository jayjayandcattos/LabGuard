"use client";

import PortalShell from "@/components/PortalShell";
import ResourcePanel from "@/components/ResourcePanel";

export default function FacultyRoomsPage() {
  return (
    <PortalShell title="Laboratory Rooms" role="faculty" links={[
      { href: "/faculty", label: "Overview" }, { href: "/faculty/schedule", label: "Schedules" },
      { href: "/faculty/rooms", label: "Rooms" },
    ]}>
      <ResourcePanel resource="rooms" title="Rooms" columns={["room_number", "room_name", "status"]} fields={["room_number", "room_name", "status"]} />
    </PortalShell>
  );
}
