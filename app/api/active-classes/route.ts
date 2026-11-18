import { Prisma } from "@prisma/client";
import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { manilaNow } from "@/lib/attendance";

type ActiveRow = {
  firstname: string;
  lastname: string;
  schedule_time: Date;
  schedule_end_time: Date | null;
  room_number: number;
  room_name: string;
  subject_name: string;
  subject_code: string;
  minutes_until_start?: number;
};

function formatTime(value: Date | null | undefined, fallback?: Date): string {
  const source = value && String(value) !== "00:00:00" ? value : fallback;
  if (!source) return "No scheduled time";
  return new Intl.DateTimeFormat("en-US", {
    timeZone: "Asia/Manila",
    hour: "2-digit",
    minute: "2-digit",
    hour12: true,
  }).format(new Date(source));
}

function dataFor(row: ActiveRow, professorSuffix = "") {
  const start = formatTime(row.schedule_time);
  const fallback = new Date(new Date(row.schedule_time).getTime() + 3 * 3600 * 1000);
  const end = formatTime(row.schedule_end_time, fallback);
  return {
    subject_name: row.subject_name,
    subject_code: row.subject_code,
    professor: `${row.lastname}, ${row.firstname}${professorSuffix}`,
    room: `Room ${row.room_number} (${row.room_name})`,
    time: `${start} - ${end}`,
  };
}

const selectJoins = Prisma.sql`
  FROM attendance_tbl a
  JOIN prof_tbl p ON a.prof_id = p.prof_user_id
  JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
  JOIN room_tbl r ON s.room_id = r.room_id
  JOIN subject_tbl sub ON s.subject_id = sub.subject_id
`;

export async function GET() {
  try {
    const { timeSql } = manilaNow();

    const active = await prisma.$queryRaw<ActiveRow[]>(Prisma.sql`
      SELECT a.attendance_id, p.firstname, p.lastname, s.schedule_time, s.schedule_end_time,
             r.room_number, r.room_name, sub.subject_name, sub.subject_code
      ${selectJoins}
      WHERE DATE(a.time_in) = CURDATE() AND a.status = 'check_in' AND a.time_out IS NULL
        AND (a.a_status = 'Present' OR a.a_status = 'Late')
        AND s.schedule_time <= ${timeSql}
        AND (s.schedule_end_time IS NULL OR s.schedule_end_time = '00:00:00' OR s.schedule_end_time > ${timeSql})
      ORDER BY a.time_in DESC LIMIT 1
    `);
    if (active[0]) {
      return NextResponse.json({ status: "success", active: true, data: dataFor(active[0]) });
    }

    const early = await prisma.$queryRaw<ActiveRow[]>(Prisma.sql`
      SELECT a.attendance_id, p.firstname, p.lastname, s.schedule_time, s.schedule_end_time,
             r.room_number, r.room_name, sub.subject_name, sub.subject_code,
             TIMESTAMPDIFF(MINUTE, ${timeSql}, s.schedule_time) AS minutes_until_start
      ${selectJoins}
      WHERE DATE(a.time_in) = CURDATE() AND a.status = 'check_in' AND a.time_out IS NULL
        AND (a.a_status = 'Present' OR a.a_status = 'Late')
        AND ${timeSql} < s.schedule_time
        AND TIMESTAMPDIFF(MINUTE, ${timeSql}, s.schedule_time) <= 30
      ORDER BY minutes_until_start ASC LIMIT 1
    `);
    if (early[0]) {
      const minutes = Math.max(0, Number(early[0].minutes_until_start ?? 0));
      return NextResponse.json({
        status: "success",
        active: true,
        data: dataFor(early[0], minutes > 0 ? ` (Starts in ${minutes} min)` : ""),
      });
    }

    const unscheduled = await prisma.$queryRaw<ActiveRow[]>(Prisma.sql`
      SELECT a.attendance_id, p.firstname, p.lastname, s.schedule_time, s.schedule_end_time,
             r.room_number, r.room_name, sub.subject_name, sub.subject_code
      ${selectJoins}
      WHERE DATE(a.time_in) = CURDATE() AND a.status = 'no_schedule' AND a.time_out IS NULL
      ORDER BY a.time_in DESC LIMIT 1
    `);
    if (unscheduled[0]) {
      return NextResponse.json({
        status: "success",
        active: false,
        message: "Professor present but not in scheduled class",
        data: { ...dataFor(unscheduled[0], " (Unscheduled)"), time: "No scheduled time" },
      });
    }

    const checkedOut = await prisma.$queryRaw<Array<{ schedule_id: number }>>`
      SELECT a.schedule_id FROM attendance_tbl a
      WHERE DATE(a.time_in) = CURDATE() AND a.prof_id IS NOT NULL AND a.status = 'check_out'
      ORDER BY a.time_in DESC LIMIT 1
    `;
    if (checkedOut[0]) {
      return NextResponse.json({ status: "success", active: false, message: "Class has ended" });
    }
    return NextResponse.json({ status: "success", active: false, message: "No active class - waiting for professor" });
  } catch (error) {
    console.error("Active class query failed:", error);
    return NextResponse.json({ status: "error", message: "Could not load active class" }, { status: 500 });
  }
}
