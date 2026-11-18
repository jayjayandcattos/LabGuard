"use client";

import { useState } from "react";
import PortalShell from "@/components/PortalShell";
import ResourcePanel from "@/components/ResourcePanel";

function SummaryForm() {
  const [email, setEmail] = useState("");
  const [section, setSection] = useState("");
  const [date, setDate] = useState(() => new Date().toISOString().slice(0, 10));
  const [message, setMessage] = useState("");

  async function submit(event: React.FormEvent) {
    event.preventDefault();
    setMessage("");
    try {
      const response = await fetch("/api/attendance/summary", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, section, date }),
      });
      const data = await response.json();
      setMessage(data.success ? "Attendance summary sent." : data.message ?? "Could not send summary.");
    } catch {
      setMessage("Could not reach the server.");
    }
  }

  return (
    <section className="resource-panel">
      <h2>Attendance Summary Email</h2>
      <form onSubmit={submit} className="resource-form">
        <input type="email" aria-label="Recipient email" placeholder="Professor email" value={email} onChange={(e) => setEmail(e.target.value)} required />
        <input aria-label="Section ID" placeholder="Section ID" value={section} onChange={(e) => setSection(e.target.value)} required />
        <input type="date" aria-label="Date" value={date} onChange={(e) => setDate(e.target.value)} required />
        <button type="submit">Send summary</button>
      </form>
      {message && <p role="status">{message}</p>}
    </section>
  );
}

export default function ProfessorPage() {
  return (
    <PortalShell
      title="Professor Dashboard"
      role="professor"
      links={[
        { href: "/professor", label: "Overview" },
        { href: "/professor/attendance", label: "Attendance" },
        { href: "/professor/schedule", label: "My Schedule" },
      ]}
    >
      <p className="portal-intro">Review class attendance, your schedule, and student rosters.</p>
      <SummaryForm />
      <ResourcePanel resource="attendance" title="Attendance Records" columns={["attendance_id", "student_id", "prof_id", "subject_id", "time_in", "status", "a_status"]} />
      <ResourcePanel resource="schedules" title="Class Schedule" columns={["schedule_id", "subject_id", "section_id", "room_id", "schedule_day", "schedule_time"]} />
      <ResourcePanel resource="students" title="Student Roster" columns={["student_id", "lastname", "firstname", "section_id"]} />
    </PortalShell>
  );
}
