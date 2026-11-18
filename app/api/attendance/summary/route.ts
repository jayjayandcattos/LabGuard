import { NextResponse } from "next/server";
import { requireRole } from "@/lib/auth";
import { prisma } from "@/lib/prisma";
import { mailFrom, mailTransport } from "@/lib/mail";

type RecordRow = {
  student_id: string;
  lastname: string;
  firstname: string;
  subject_name: string | null;
  attendance_status: string;
  time_in: string | null;
  time_out: string | null;
};

function escapeHtml(value: string): string {
  return value.replace(/[&<>"']/g, (c) =>
    ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c] ?? c,
  );
}

export async function POST(request: Request) {
  const guard = await requireRole("professor");
  if (guard.response) return guard.response;

  let body: { email?: string; section?: string; date?: string };
  try {
    body = await request.json();
  } catch {
    return NextResponse.json({ success: false, message: "Invalid request" }, { status: 400 });
  }

  const { email, section, date } = body;
  if (!email || !section || !date || Number.isNaN(Date.parse(date))) {
    return NextResponse.json({ success: false, message: "Missing or invalid parameters" }, { status: 400 });
  }

  try {
    const sqlDate = new Date(date).toISOString().slice(0, 10);
    const displayDate = new Intl.DateTimeFormat("en-US", {
      year: "numeric", month: "long", day: "numeric", timeZone: "UTC",
    }).format(new Date(`${sqlDate}T00:00:00Z`));

    const records = await prisma.$queryRaw<RecordRow[]>`
      SELECT st.student_id, st.lastname, st.firstname, sub.subject_name, sch.schedule_day,
        CASE WHEN MAX(a.a_status) IN ('Present') THEN 'Present'
             WHEN COUNT(CASE WHEN a.status = 'check_in' OR a.status = 'check_out' THEN 1 END) > 0 THEN 'Present'
             ELSE 'Absent' END AS attendance_status,
        MIN(CASE WHEN a.status = 'check_in' OR a.status = 'check_out' THEN TIME(a.time_in) END) AS time_in,
        MAX(CASE WHEN a.status = 'check_out' THEN TIME(a.time_out) END) AS time_out
      FROM student_tbl st
      LEFT JOIN attendance_tbl a ON st.student_user_id = a.student_id AND DATE(a.time_in) = ${sqlDate}
      LEFT JOIN schedule_tbl sch ON st.section_id = sch.section_id AND sch.schedule_day = DAYNAME(${sqlDate})
      LEFT JOIN subject_tbl sub ON sch.subject_id = sub.subject_id
      WHERE st.section_id = ${section}
      GROUP BY st.student_id, st.lastname, st.firstname, sub.subject_name, sch.schedule_day
      ORDER BY st.lastname, st.firstname
    `;

    const sections = await prisma.$queryRaw<Array<{ section_name: string }>>`
      SELECT section_name FROM section_tbl WHERE section_id = ${section}
    `;
    const sectionName = sections[0]?.section_name ?? section;

    const rows = records
      .map(
        (r) =>
          `<tr><td>${escapeHtml(r.student_id)}</td><td>${escapeHtml(`${r.lastname}, ${r.firstname}`)}</td>` +
          `<td>${escapeHtml(r.subject_name ?? "-")}</td><td>${escapeHtml(r.attendance_status)}</td>` +
          `<td>${escapeHtml(r.time_in ?? "-")}</td><td>${escapeHtml(r.time_out ?? "-")}</td></tr>`,
      )
      .join("");
    const html =
      `<html><body><h2>Attendance Summary</h2><p>Good Day Professor!</p>` +
      `<p>Summary for ${escapeHtml(sectionName)} on ${escapeHtml(displayDate)}.</p>` +
      `<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse">` +
      `<tr><th>Student ID</th><th>Name</th><th>Subject</th><th>Status</th><th>Time In</th><th>Time Out</th></tr>` +
      rows +
      `</table></body></html>`;

    const from = mailFrom();
    await mailTransport().sendMail({
      from: { address: from.address, name: from.name },
      to: email,
      subject: `Attendance Summary - ${sectionName} - ${date}`,
      html,
    });

    return NextResponse.json({ success: true, message: "Email sent successfully" });
  } catch (error) {
    console.error("Error sending attendance summary:", error);
    return NextResponse.json({ success: false, message: "Error sending email" }, { status: 500 });
  }
}
