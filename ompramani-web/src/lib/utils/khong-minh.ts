/**
 * Lịch Xuất Hành Khổng Minh (Gia Cát Lượng)
 * Dựa trên ngày và tháng Âm lịch truyền thống Việt Nam.
 */

export interface KhongMinhXuatHanhInfo {
  name: string;
  isGood: boolean;
  description: string;
}

export function getKhongMinhXuatHanh(lunarMonth: number, lunarDay: number): KhongMinhXuatHanhInfo {
  // Chuẩn hóa tháng từ 1..12 và ngày 1..30
  const month = ((lunarMonth - 1) % 12) + 1;
  const day = Math.max(1, Math.min(30, lunarDay));

  // Nhóm tháng 1, 4, 7, 10 (Chu kỳ 6 ngày)
  if (month === 1 || month === 4 || month === 7 || month === 10) {
    const cycle: KhongMinhXuatHanhInfo[] = [
      {
        name: 'Đường Phong',
        isGood: true,
        description: 'Rất tốt, xuất hành thuận lợi, cầu tài được như ý muốn, gặp quý nhân phù trợ.',
      },
      {
        name: 'Kim Thổ',
        isGood: false,
        description: 'Ra đi nhỡ tàu xe, cầu tài không được, trên đường đi dễ gặp bất lợi, mất của.',
      },
      {
        name: 'Kim Dương',
        isGood: true,
        description: 'Xuất hành tốt, có quý nhân phù trợ, tài lộc thông suốt, thưa kiện có lý phải.',
      },
      {
        name: 'Thuần Dương',
        isGood: true,
        description: 'Xuất hành tốt, lúc về cũng tốt, nhiều thuận lợi, được người tốt giúp đỡ, cầu tài được như ý muốn, tranh luận thường thắng lợi.',
      },
      {
        name: 'Đạo Tặc',
        isGood: false,
        description: 'Rất xấu, xuất hành dễ bị cản trở, hao tổn tài sản, nên cẩn trọng giữ gìn an toàn.',
      },
      {
        name: 'Hảo Thương',
        isGood: true,
        description: 'Xuất hành thuận lợi, gặp người lớn vừa lòng, làm việc như ý muốn, cầu tài đắc tài.',
      },
    ];
    return cycle[(day - 1) % 6];
  }

  // Nhóm tháng 2, 5, 8, 11 (Chu kỳ 8 ngày)
  if (month === 2 || month === 5 || month === 8 || month === 11) {
    const cycle: KhongMinhXuatHanhInfo[] = [
      {
        name: 'Thiên Đạo',
        isGood: false,
        description: 'Cầu tài nên tránh, dù được cũng hao tốn, kiện tụng nên dĩ hòa vi quý.',
      },
      {
        name: 'Thiên Môn',
        isGood: true,
        description: 'Xuất hành mọi việc đều vừa ý, cầu được ước thấy, vạn sự hanh thông thành đạt.',
      },
      {
        name: 'Thiên Đường',
        isGood: true,
        description: 'Quý nhân phù trợ, buôn bán may mắn, vạn sự như ý, gia đạo an khang.',
      },
      {
        name: 'Thiên Tài',
        isGood: true,
        description: 'Nên xuất hành, cầu tài thắng lợi, đi xa có người giúp đỡ, hanh thông.',
      },
      {
        name: 'Thiên Tặc',
        isGood: false,
        description: 'Cầu tài không được, đi đường dễ mất cắp, việc gì cũng có trở ngại, nên cẩn thận.',
      },
      {
        name: 'Thiên Đương',
        isGood: true,
        description: 'Xuất hành tốt, cầu tài được tài, gặp gỡ giao thiệp việc gì cũng thuận lợi.',
      },
      {
        name: 'Thiên Hầu',
        isGood: false,
        description: 'Dễ có tranh chấp thị phi, cần đề phòng va chạm, nên nhẫn nhịn giữ an.',
      },
      {
        name: 'Thiên Thương',
        isGood: true,
        description: 'Rất tốt khi gặp cấp trên hoặc cầu tài, công việc hanh thông, mọi sự viên thành.',
      },
    ];
    return cycle[(day - 1) % 8];
  }

  // Nhóm tháng 3, 6, 9, 12 (Chu kỳ 8 ngày)
  const cycle: KhongMinhXuatHanhInfo[] = [
    {
      name: 'Chu Tước',
      isGood: false,
      description: 'Cầu tài bất lợi, dễ mất của, kiện cáo thua vì đuối lý, không nên đi xa.',
    },
    {
      name: 'Bạch Hổ Đầu',
      isGood: true,
      description: 'Xuất hành, cầu tài đều được, đi đến đâu cũng thông đạt, mọi việc vừa ý hanh thông.',
    },
    {
      name: 'Bạch Hổ Kiếp',
      isGood: true,
      description: 'Cầu tài như ý, đi hướng Nam hoặc Bắc rất thuận lợi, công việc suôn sẻ.',
    },
    {
      name: 'Bạch Hổ Túc',
      isGood: false,
      description: 'Cấm đi xa, mưu sự khó thành, nên giữ bình an và thận trọng.',
    },
    {
      name: 'Huyền Vũ',
      isGood: false,
      description: 'Dễ gặp cãi cọ rắc rối, mưu sự chậm trễ, không nên xuất hành đi xa.',
    },
    {
      name: 'Thanh Long Đầu',
      isGood: true,
      description: 'Nên đi vào sáng sớm, cầu tài thắng lợi, vạn sự như ý, quý nhân tương trợ.',
    },
    {
      name: 'Thanh Long Kiếp',
      isGood: true,
      description: 'Xuất hành bốn phương tám hướng đều tốt, trăm sự như ý, trăm việc hanh thông.',
    },
    {
      name: 'Thanh Long Túc',
      isGood: false,
      description: 'Đi xa bất lợi, tài lộc không có, kiện cáo đuối lý, nên giữ tâm an ổn.',
    },
  ];
  return cycle[(day - 1) % 8];
}
