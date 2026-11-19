export interface UserProfile {
  name: string;
  avatarUrl: string;
  mindfulGreeting: string;
  auspiciousEventTitle: string;
  auspiciousEventDetail: string;
}

export const DEMO_USER: UserProfile = {
  name: "Trí Duy",
  avatarUrl:
    "https://lh3.googleusercontent.com/aida-public/AB6AXuDNeg6QMkqlE_T7IypE7Y38aoiDw3p6p5fhNIXA-ae3RfRl4tqxjJuRx4xIeNYn3YYg-wvGlP9tRdUuJ7W1ztKxX_B0lSGwWy6M1zalbD2atyl7SCAqsFGnsneXBCmWlRn1XcOOIAxsSMYl0wG5R71HP38pCBoQU5H5nsN_Ft6S1HldI3cfo0n_MPw_2HtHjqHHM7fCPcruG5B2gzD4-afqJjL8suaJbZ_ahDDsYfU",
  mindfulGreeting:
    "Chúc bạn một ngày an lạc, thân tâm tự tại và tu tập tinh tấn.",
  auspiciousEventTitle: "Lịch Pháp Sự",
  auspiciousEventDetail: "Hôm nay: Ngày Thọ Bát Quan Trai",
};
