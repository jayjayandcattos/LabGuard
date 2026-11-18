import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { manilaNow } from "@/lib/attendance";

export async function GET() {
  try {
    const { timeSql } = manilaNow();

    const ended = await prisma.$queryRaw<Array<{ attendance_id: number }>>`
      SELECT a.attendance_id
      FROM attendance_tbl a JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
      WHERE DATE(a.time_in) = CURDATE() AND a.status = 'check_in' AND a.time_out IS NULL
        AND s.schedule_end_time IS NOT NULL AND s.schedule_end_time != '00:00:00'
        AND s.schedule_end_time < ${timeSql}
    `;

    if (ended.length === 0) {
      return NextResponse.json({ status: "success", message: "No ended classes found that need updates" });
    }

    await prisma.$executeRaw`
      UPDATE attendance_tbl a JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
      SET a.status = 'no_schedule', a.a_status = 'No Schedule'
      WHERE a.prof_id IS NOT NULL AND a.status = 'check_in' AND a.time_out IS NULL
        AND DATE(a.time_in) = CURDATE() AND s.schedule_end_time IS NOT NULL
        AND s.schedule_end_time != '00:00:00' AND s.schedule_end_time < ${timeSql}
    `;

    return NextResponse.json({
      status: "success",
      message: `Updated ${ended.length} professors with ended classes`,
      updated_professors: ended,
    });
  } catch (error) {
    console.error("update-ended-classes failed:", error);
    return NextResponse.json({ status: "error", message: "Could not update ended classes" }, { status: 500 });
  }
}
