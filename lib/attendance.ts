import { prisma } from "./prisma";

export type TapUser = {
  id: string;
  lastname: string;
  firstname: string;
  photo: string | null;
  role: "student" | "professor";
  section_id: number | null;
};

export type TapResult = {
  success: boolean;
  message: string;
  time: string;
  user?: TapUser;
  status?: string;
  a_status?: string;
  room?: { room_number: number; room_name: string } | null;
  checked_out_students?: Array<{ user: { lastname: string; firstname: string; photo: string | null }; check_in_time: string }>;
};

type Schedule = {
  schedule_id: number;
  subject_id: number;
  section_id: number;
  room_id: number;
  schedule_time?: Date | string;
};

/** All times are computed in Asia/Manila, matching the legacy PHP kiosk. */
function manilaNow() {
  const now = new Date();
  const parts = Object.fromEntries(
    new Intl.DateTimeFormat("en-US", {
      timeZone: "Asia/Manila",
      weekday: "long",
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
      hourCycle: "h23",
    })
      .formatToParts(now)
      .map((p) => [p.type, p.value]),
  );
  const datetime = `${parts.year}-${parts.month}-${parts.day} ${parts.hour}:${parts.minute}:${parts.second}`;
  const timeSql = `${parts.hour}:${parts.minute}:${parts.second}`;
  const formatted = new Intl.DateTimeFormat("en-US", {
    timeZone: "Asia/Manila",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: true,
  }).format(now);
  return { datetime, timeSql, day: parts.weekday, formatted };
}

function secondsBetween(nowTime: string, scheduleTime: Date | string): number {
  const toSeconds = (value: string) => {
    const [h, m, s] = value.split(":").map(Number);
    return h * 3600 + m * 60 + (s || 0);
  };
  const scheduleStr =
    scheduleTime instanceof Date
      ? scheduleTime.toISOString().slice(11, 19)
      : String(scheduleTime).slice(0, 8);
  return toSeconds(nowTime) - toSeconds(scheduleStr);
}

async function findUser(rfidTag: string): Promise<TapUser | null> {
  const student = await prisma.student_tbl.findFirst({
    where: { rfid_tag: rfidTag },
    select: {
      student_user_id: true,
      lastname: true,
      firstname: true,
      photo: true,
      section_id: true,
    },
  });
  if (student) {
    return {
      id: String(student.student_user_id),
      lastname: student.lastname,
      firstname: student.firstname,
      photo: student.photo,
      role: "student",
      section_id: student.section_id,
    };
  }

  const prof = await prisma.prof_tbl.findFirst({
    where: { rfid_tag: rfidTag },
    select: { prof_user_id: true, lastname: true, firstname: true, photo: true },
  });
  if (prof) {
    return {
      id: String(prof.prof_user_id),
      lastname: prof.lastname,
      firstname: prof.firstname,
      photo: prof.photo,
      role: "professor",
      section_id: null,
    };
  }
  return null;
}

async function roomInfo(roomId: number | null) {
  if (roomId == null) return null;
  const room = await prisma.room_tbl.findUnique({ where: { room_id: roomId } });
  return room ? { room_number: room.room_number, room_name: room.room_name } : null;
}

async function insertAttendance(data: {
  student_id?: number | null;
  prof_id?: number | null;
  subject_id: number;
  schedule_id: number;
  room_id?: number | null;
  rfid_tag: string;
  time_in: string;
  status: "check_in" | "check_out" | "no_schedule" | "wrong_class";
  a_status: "Present" | "Absent" | "Late" | "Ended" | "No Schedule" | "Wrong Class";
}) {
  await prisma.$executeRaw`
    INSERT INTO attendance_tbl
      (student_id, prof_id, subject_id, schedule_id, room_id, rfid_tag, time_in, status, a_status)
    VALUES
      (${data.student_id ?? null}, ${data.prof_id ?? null}, ${data.subject_id},
       ${data.schedule_id}, ${data.room_id ?? null}, ${data.rfid_tag},
       ${data.time_in}, ${data.status}, ${data.a_status})
  `;
  const rows = await prisma.$queryRaw<Array<{ id: bigint }>>`SELECT LAST_INSERT_ID() AS id`;
  return Number(rows[0]?.id ?? 0);
}

export async function processTap(rawTag: string): Promise<TapResult> {
  const rfidTag = rawTag.trim();
  const { datetime, timeSql, day, formatted } = manilaNow();

  const user = await findUser(rfidTag);
  if (!user) {
    return {
      success: false,
      message: `Scanned tag: ${rfidTag}. Please make sure this card is registered.`,
      time: formatted,
    };
  }

  // Expire professors whose schedule has already ended.
  await prisma.$executeRaw`
    UPDATE attendance_tbl a JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
    SET a.status = 'no_schedule', a.a_status = 'No Schedule'
    WHERE a.prof_id IS NOT NULL AND a.status = 'check_in' AND a.time_out IS NULL
      AND DATE(a.time_in) = CURDATE() AND s.schedule_end_time IS NOT NULL
      AND s.schedule_end_time != '00:00:00' AND s.schedule_end_time < ${timeSql}
  `;

  const lastTap = await prisma.$queryRaw<Array<{ time_out: Date | null }>>`
    SELECT time_out FROM attendance_tbl
    WHERE rfid_tag = ${rfidTag} AND DATE(time_in) = CURDATE()
    ORDER BY time_in DESC LIMIT 1
  `;
  const nextAction = lastTap.length > 0 && lastTap[0].time_out === null ? "check_out" : "check_in";

  let schedule: Schedule | undefined;

  if (user.role === "professor") {
    const schedules = await prisma.$queryRaw<Schedule[]>`
      SELECT s.schedule_id, s.subject_id, s.section_id, s.room_id, s.schedule_time
      FROM schedule_tbl s
      WHERE s.schedule_day = ${day}
        AND TIME(ADDTIME(s.schedule_time, '-00:30:00')) <= ${timeSql}
        AND ADDTIME(s.schedule_time, '03:30:00') >= ${timeSql}
        AND s.prof_user_id = ${Number(user.id)}
      ORDER BY ABS(TIME_TO_SEC(TIMEDIFF(s.schedule_time, ${timeSql}))) ASC LIMIT 1
    `;
    schedule = schedules[0];

    if (!schedule && nextAction === "check_in") {
      // No schedule: mirror the legacy fallback to any subject/schedule so the record can be stored.
      let subject = await prisma.subject_tbl.findFirst({ where: { prof_user_id: Number(user.id) } });
      if (!subject) subject = await prisma.subject_tbl.findFirst();
      const anySchedule = await prisma.schedule_tbl.findFirst();
      if (!subject || !anySchedule) {
        return { success: false, message: "System error: No subjects or schedules found in database", time: formatted };
      }
      await insertAttendance({
        prof_id: Number(user.id),
        subject_id: subject.subject_id,
        schedule_id: anySchedule.schedule_id,
        rfid_tag: rfidTag,
        time_in: datetime,
        status: "no_schedule",
        a_status: "No Schedule",
      });
      return {
        success: true,
        message: "No valid schedule found for check-in.",
        time: formatted,
        user,
        status: "no_schedule",
        a_status: "No Schedule",
      };
    }
  } else {
    const active = await prisma.$queryRaw<Schedule[]>`
      SELECT a.schedule_id, a.subject_id, s.section_id, s.room_id
      FROM attendance_tbl a JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
      WHERE DATE(a.time_in) = CURDATE() AND a.prof_id IS NOT NULL
        AND (a.status = 'check_in' OR a.status = 'no_schedule')
      ORDER BY a.time_in DESC LIMIT 1
    `;
    const profSchedule = active[0];
    if (!profSchedule) {
      return { success: false, message: "Please wait for your professor to check in first.", time: formatted, user };
    }
    if (user.section_id !== profSchedule.section_id && nextAction === "check_in") {
      await insertAttendance({
        student_id: Number(user.id),
        subject_id: profSchedule.subject_id,
        schedule_id: profSchedule.schedule_id,
        room_id: profSchedule.room_id,
        rfid_tag: rfidTag,
        time_in: datetime,
        status: "wrong_class",
        a_status: "Wrong Class",
      });
      return {
        success: false,
        message: "You are not enrolled in this class section.",
        time: formatted,
        user,
        status: "wrong_class",
        a_status: "Wrong Class",
      };
    }
    schedule = profSchedule;
  }

  if (!schedule) {
    return { success: false, message: "No active schedule found.", time: formatted, user };
  }

  if (user.role === "professor") {
    if (nextAction === "check_in") {
      await insertAttendance({
        prof_id: Number(user.id),
        subject_id: schedule.subject_id,
        schedule_id: schedule.schedule_id,
        room_id: schedule.room_id,
        rfid_tag: rfidTag,
        time_in: datetime,
        status: "check_in",
        a_status: "Present",
      });

      const diff = schedule.schedule_time ? secondsBetween(timeSql, schedule.schedule_time) : 0;
      let message = "Professor check-in recorded";
      let aStatus: TapResult["a_status"] = "Present";
      if (diff < -60) {
        message = `Professor check-in recorded (${Math.abs(Math.round(diff / 60))} minutes early)`;
      } else if (diff > 60 && diff < 900) {
        message = `Professor check-in recorded (${Math.round(diff / 60)} minutes late)`;
      } else if (diff >= 900) {
        message = `Professor check-in recorded (${Math.round(diff / 60)} minutes late - marked as LATE)`;
        aStatus = "Late";
        await prisma.$executeRaw`
          UPDATE attendance_tbl SET a_status = 'Late'
          WHERE prof_id = ${Number(user.id)} AND DATE(time_in) = CURDATE()
          ORDER BY time_in DESC LIMIT 1
        `;
      }
      return {
        success: true,
        message,
        user,
        status: "check_in",
        time: formatted,
        a_status: aStatus,
        room: await roomInfo(schedule.room_id),
      };
    }

    // Professor check-out also checks out their students.
    await prisma.$executeRaw`
      UPDATE attendance_tbl SET time_out = ${datetime}, status = 'check_out', a_status = 'Ended'
      WHERE prof_id = ${Number(user.id)} AND DATE(time_in) = CURDATE() AND status = 'check_in'
    `;

    const profSchedules = await prisma.$queryRaw<Array<{ schedule_id: number }>>`
      SELECT a.schedule_id FROM attendance_tbl a
      JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
      JOIN room_tbl r ON s.room_id = r.room_id
      WHERE a.prof_id = ${Number(user.id)} AND DATE(a.time_in) = CURDATE()
      ORDER BY a.time_in DESC LIMIT 1
    `;
    const checkedSchedule = profSchedules[0]?.schedule_id;

    if (checkedSchedule) {
      await prisma.$executeRaw`
        UPDATE attendance_tbl
        SET time_out = ${datetime}, status = 'check_out',
            a_status = CASE WHEN a_status = 'Present' THEN 'Present' ELSE 'Absent' END
        WHERE student_id IS NOT NULL AND schedule_id = ${checkedSchedule}
          AND DATE(time_in) = CURDATE() AND status = 'check_in'
      `;
    }

    const students = await prisma.$queryRaw<
      Array<{ lastname: string; firstname: string; photo: string | null; time_in: Date }>
    >`
      SELECT s.lastname, s.firstname, s.photo, a.time_in
      FROM attendance_tbl a JOIN student_tbl s ON a.student_id = s.student_user_id
      WHERE a.schedule_id = ${checkedSchedule ?? -1} AND DATE(a.time_in) = CURDATE()
    `;

    return {
      success: true,
      message: "Professor checked out. All students have been checked out automatically.",
      user,
      status: "check_out",
      time: formatted,
      a_status: "Ended",
      checked_out_students: students.map((s) => ({
        user: { lastname: s.lastname, firstname: s.firstname, photo: s.photo },
        check_in_time: new Intl.DateTimeFormat("en-US", {
          timeZone: "Asia/Manila", hour: "2-digit", minute: "2-digit", second: "2-digit", hour12: true,
        }).format(new Date(s.time_in)),
      })),
      room: await roomInfo(schedule.room_id),
    };
  }

  // Student
  if (nextAction === "check_in") {
    const already = await prisma.$queryRaw<Array<{ attendance_id: number }>>`
      SELECT attendance_id FROM attendance_tbl
      WHERE student_id = ${Number(user.id)} AND DATE(time_in) = CURDATE() AND status = 'check_in'
    `;
    if (already.length > 0) {
      return { success: false, message: "You have already checked in today.", time: formatted, user };
    }
    if (user.section_id !== schedule.section_id) {
      await insertAttendance({
        student_id: Number(user.id),
        subject_id: schedule.subject_id,
        schedule_id: schedule.schedule_id,
        room_id: schedule.room_id,
        rfid_tag: rfidTag,
        time_in: datetime,
        status: "wrong_class",
        a_status: "Wrong Class",
      });
      return {
        success: false,
        message: "You are not enrolled in this class section.",
        time: formatted,
        user,
        status: "wrong_class",
        a_status: "Wrong Class",
      };
    }

    const id = await insertAttendance({
      student_id: Number(user.id),
      subject_id: schedule.subject_id,
      schedule_id: schedule.schedule_id,
      room_id: schedule.room_id,
      rfid_tag: rfidTag,
      time_in: datetime,
      status: "check_in",
      a_status: "Present",
    });

    const scheduleTime =
      schedule.schedule_time ??
      (
        await prisma.$queryRaw<Array<{ schedule_time: Date }>>`
          SELECT schedule_time FROM schedule_tbl WHERE schedule_id = ${schedule.schedule_id}
        `
      )[0]?.schedule_time;

    const diff = scheduleTime ? secondsBetween(timeSql, scheduleTime) : 0;
    let message = "Student check-in recorded successfully!";
    if (diff < -60) {
      message = `Student check-in recorded (${Math.abs(Math.round(diff / 60))} minutes early)`;
    } else if (diff > 60 && diff < 900) {
      message = `Student check-in recorded (${Math.round(diff / 60)} minutes late)`;
    } else if (diff >= 900) {
      message = `Student check-in recorded (${Math.round(diff / 60)} minutes late - marked as LATE)`;
      await prisma.$executeRaw`UPDATE attendance_tbl SET a_status = 'Late' WHERE attendance_id = ${id}`;
    }

    return {
      success: true,
      message,
      user,
      status: "check_in",
      time: formatted,
      a_status: "Present",
      room: await roomInfo(schedule.room_id),
    };
  }

  // Student check-out
  const attendance = await prisma.$queryRaw<Array<{ room_id: number | null }>>`
    SELECT a.room_id FROM attendance_tbl a
    JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
    JOIN room_tbl r ON a.room_id = r.room_id
    WHERE a.student_id = ${Number(user.id)} AND DATE(a.time_in) = CURDATE() AND a.status = 'check_in'
  `;
  await prisma.$executeRaw`
    UPDATE attendance_tbl SET time_out = ${datetime}, status = 'check_out', a_status = 'Ended'
    WHERE student_id = ${Number(user.id)} AND DATE(time_in) = CURDATE() AND status = 'check_in'
  `;
  return {
    success: true,
    message: "Student check-out recorded successfully!",
    user,
    status: "check_out",
    time: formatted,
    a_status: "Ended",
    room: await roomInfo(attendance[0]?.room_id ?? schedule.room_id ?? null),
  };
}
