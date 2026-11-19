import { apiClient } from './client';
import {
  AlmanacDayData,
  AlmanacDayResponse,
  AlmanacMonthData,
  AlmanacMonthResponse,
  AlmanacMethodMeta,
  AlmanacMethodsResponse,
  AlmanacConfigData,
  AlmanacConfigResponse,
} from '@/types/almanac';

/**
 * Get almanac day data (Can Chi, Day classification, 12 Earthly branch hours, Auspicious hours).
 *
 * @param params { date?: 'YYYY-MM-DD', method?: string }
 */
export async function getAlmanacDay(params?: {
  date?: string;
  method?: string;
}): Promise<AlmanacDayData> {
  const query = new URLSearchParams();
  if (params?.date) query.set('date', params.date);
  if (params?.method) query.set('method', params.method);

  const url = query.toString()
    ? `/api/v1/calendar/almanac/day?${query.toString()}`
    : `/api/v1/calendar/almanac/day`;

  const res = await apiClient<AlmanacDayResponse>(url, {
    method: 'GET',
    cache: params?.date ? 'force-cache' : 'no-store',
  });

  return res.data;
}

/**
 * Get almanac month summaries for all days in a month.
 *
 * @param params { year: number, month: number, method?: string }
 */
export async function getAlmanacMonth(params: {
  year: number;
  month: number;
  method?: string;
}): Promise<AlmanacMonthData> {
  const query = new URLSearchParams({
    year: params.year.toString(),
    month: params.month.toString(),
  });
  if (params.method) query.set('method', params.method);

  const res = await apiClient<AlmanacMonthResponse>(
    `/api/v1/calendar/almanac/month?${query.toString()}`,
    {
      method: 'GET',
      cache: 'force-cache',
    }
  );

  return res.data;
}

/**
 * Get list of available traditional almanac methods and metadata.
 */
export async function getAlmanacMethods(): Promise<AlmanacMethodMeta[]> {
  const res = await apiClient<AlmanacMethodsResponse>(
    '/api/v1/calendar/almanac/methods',
    {
      method: 'GET',
      cache: 'force-cache',
    }
  );

  return res.data;
}

/**
 * Get traditional almanac system configuration and registries.
 */
export async function getAlmanacConfig(): Promise<AlmanacConfigData> {
  const res = await apiClient<AlmanacConfigResponse>(
    '/api/v1/calendar/almanac/config',
    {
      method: 'GET',
      cache: 'force-cache',
    }
  );

  return res.data;
}
