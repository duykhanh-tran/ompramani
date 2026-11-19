export type TempleType =
  | "temple"
  | "meditation_monastery"
  | "monastery"
  | "vihara"
  | "practice_center"
  | "buddhist_center"
  | "other";

export type TempleVerificationStatus =
  | "unverified"
  | "pending_review"
  | "verified"
  | "disputed";

export type TemplePublicationStatus =
  | "draft"
  | "in_review"
  | "published"
  | "archived";

export type TemplePublicAccessStatus =
  | "publicly_accessible"
  | "restricted"
  | "by_appointment"
  | "unknown";

export type TempleCoordinateAccuracy =
  | "exact_verified"
  | "approximate"
  | "unavailable";

export type TempleSourceType =
  | "official_website"
  | "government_registry"
  | "editorial_verification"
  | "book_document"
  | "field_survey"
  | "other";

export interface TempleTypeRegistryItem {
  key: TempleType;
  label: string;
  description: string;
}

export interface TempleCoordinates {
  latitude: number;
  longitude: number;
  accuracy: TempleCoordinateAccuracy;
  accuracy_label?: string;
}

export interface TempleAddress {
  formatted: string;
  line?: string | null;
  ward?: string | null;
  district?: string | null;
  province?: string | null;
  country_code: string;
  postal_code?: string | null;
}

export interface TempleContact {
  phone?: string | null;
  email?: string | null;
  website?: string | null;
}

export interface TempleTraditionItem {
  id: number;
  name: string;
  slug: string;
  is_primary: boolean;
}

export interface TempleMediaItem {
  id: number;
  file_path: string;
  file_name?: string | null;
  alt_text?: string | null;
  caption?: string | null;
  credit?: string | null;
  license_info?: string | null;
  sort_order: number;
  is_cover: boolean;
}

export interface TempleSourceItem {
  title: string;
  url?: string | null;
  type: TempleSourceType;
  type_label?: string;
  reference?: string | null;
  checked_at?: string | null;
}

export interface TempleSummary {
  public_id: string;
  slug: string;
  name: string;
  place_type: TempleType;
  place_type_label: string;
  short_description?: string | null;
  construction_year?: string | null;
  opening_hours?: string | null;
  admission_fee?: number | null;
  google_maps_url?: string | null;
  formatted_address: string;
  province_name?: string | null;
  country_code: string;
  cover_image?: string | null;
  traditions: TempleTraditionItem[];
  has_verified_coordinates: boolean;
  published_at?: string | null;
}

export interface TempleVisitInformation {
  access_status: TemplePublicAccessStatus;
  access_status_label: string;
  notes?: string | null;
  verified_at?: string | null;
  source?: {
    title: string;
    url?: string | null;
  } | null;
}

export interface TempleDetail {
  id: number;
  public_id: string;
  slug: string;
  name: string;
  alternative_names?: string[] | { name: string; language?: string; note?: string }[] | null;
  place_type: TempleType;
  place_type_label: string;
  short_description?: string | null;
  description?: string | null;
  construction_year?: string | null;
  opening_hours?: string | null;
  admission_fee?: number | null;
  highlights?: string | null;
  history?: string | null;
  architecture_culture?: string | null;
  google_maps_url?: string | null;
  address: TempleAddress;
  coordinates?: TempleCoordinates | null;
  public_access_status: TemplePublicAccessStatus;
  public_access_status_label: string;
  visit_information?: TempleVisitInformation | null;
  contacts: TempleContact;
  traditions: TempleTraditionItem[];
  cover_image?: string | null;
  images: TempleMediaItem[];
  sources: TempleSourceItem[];
  last_verified_at?: string | null;
  published_at?: string | null;
}

export interface TempleFilterFacets {
  types: TempleTypeRegistryItem[];
  provinces: string[];
  countries: string[];
  traditions: { id: number; name: string; slug: string }[];
}

export interface TempleSearchParams {
  q?: string;
  type?: TempleType;
  place_type?: TempleType;
  country?: string;
  province?: string;
  tradition?: string | number;
  sort?: "relevance" | "name_asc" | "name_desc" | "newest";
  page?: number;
  per_page?: number;
}
