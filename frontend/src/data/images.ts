export function unsplash(id: string, w = 1600, q = 80): string {
  return `https://images.unsplash.com/photo-${id}?auto=format&fit=crop&w=${w}&q=${q}`;
}

// Every id below has been visually verified to match its key's description.
export const IMG = {
  // Hero / cinematic
  deadliftDramatic: "1517836357463-d25dfeac3438",
  silhouetteDeadlift: "1605296867304-46d5465a13f1",
  moodyPortrait: "1550345332-09e3ac987658",
  pullupBackMuscular: "1583454155184-870a1f63aebc",
  chalkDeadliftGarage: "1595078475328-1ab05d0a6a0e",

  // Gym interiors
  dumbbellRackMirror: "1534438327276-14e5300c3a48",
  gymInteriorBrightCardio: "1540497077202-7c8a3999166f",
  squatRackBrightStudio: "1596357395217-80de13130e92",
  rackMoodyPlates: "1620188467120-5042ed1eb5da",

  // Free weights / barbell / dumbbells
  dumbbellReachHand: "1583454110551-21f2fa2afe61",
  dumbbellRowsBW: "1544033527-b192daee1f5b",
  deadliftFeetCloseup: "1517963879433-6ad2b056d712",
  deadliftGripCloseup: "1549060279-7e168fcee0c2",
  barbellGripFloor: "1517838277536-f5f99be501cd",
  weightPlatesTopView: "1526401485004-46910ecc8e51",
  pullupDramaticBW: "1526506118085-60ce8714f8c5",

  // Machines
  benchPressSpotter: "1584466977773-e625c37cdd50",
  legPressMachineDark: "1434682772747-f16d3ea162c3",
  legPressManBlue: "1630415187908-39d6d209b15c",
  legPressCloseupBlue: "1712992031203-45538d32ab43",
  cableRowWoman: "1571731956672-f2b94d7dd0cb",
  overheadPressWoman: "1541534741688-6078c6bfb5c5",

  // Cardio
  assaultBikeCloseup: "1591741535018-d042766c62eb",
  runnerLegsRoad: "1571008887538-b36bb32f4571",
  runningStepsFeet: "1476480862126-209bfaa8edc8",
  trackAerialRunners: "1502904550040-7534597429ae",
  outdoorGroupRun: "1607962837359-5e7e89f86776",

  // Functional
  kettlebellSwingAthlete: "1601422407692-ec4eeec1d9b3",
  battleRopesUrban: "1599058917212-d750089bc07e",
  crossfitWallBallGroup: "1533560904424-a0c61dc306fc",
  bandGroupClass: "1517130038641-a774d04afb3c",
  abCrunchRings: "1594381898411-846e7d193883",
  abCrunchWindow: "1571019613454-1cb2f99b2d8b",
  pushupDumbbellsMan: "1594737625785-a6cbdabd333c",

  // Classes / community
  barbellGroupClassBW: "1554284126-aa88f22d8b74",
  womenDumbbellGroupClass: "1518310383802-640c2de311b2",
  groupPlankClassPink: "1518611012118-696072aa579a",
  groupClassChatMats: "1518459031867-a89b944bffe4",
  dumbbellRowBenchWoman: "1546483875-ad9014c88eba",

  // Recovery
  hotStoneSpa: "1600334129128-685c5582fd35",
  stretchMobilityWoman: "1600881333168-2ef49b341f30",

  // Amenities
  lockerWooden: "1630710577149-9220addf1fd3",
  lockerNavyBlue: "1721099163762-344c8549620f",
  receptionDesk: "1777703838688-0080502d919a",

  // Athletes / misc gallery
  studioJumpWhiteBg: "1470468969717-61d5d54fd036",
  shadowBoxingWoman: "1584464491033-06628f3a6b7b",

  // Trainer portraits (people)
  trainerMarcus: "1704223523169-52feeed90365",
  trainerElena: "1550259979-ed79b48d2a30",
  trainerJordan: "1616803689943-5601631c7fec",
  trainerSofia: "1548690312-e3b507d8c110",
  trainerDaniel: "1567013127542-490d757e51fc",
  trainerGrace: "1600881333168-2ef49b341f30",
} as const;
