// Mock service layer. Swap the bodies of these functions for real
// `fetch`/axios calls to a Laravel REST API without touching callers.
import { equipment } from "../data/equipment";
import { facilities } from "../data/facilities";
import { galleryImages } from "../data/gallery";
import { membershipPlans } from "../data/memberships";
import { programs } from "../data/programs";
import { testimonials } from "../data/testimonials";
import { trainers } from "../data/trainers";
import type {
  Equipment,
  Facility,
  GalleryImage,
  MembershipPlan,
  Program,
  Testimonial,
  Trainer,
} from "../types";

function mockRequest<T>(data: T, delay = 250): Promise<T> {
  return new Promise((resolve) => setTimeout(() => resolve(data), delay));
}

export const gymService = {
  getFacilities: (): Promise<Facility[]> => mockRequest(facilities),
  getEquipment: (): Promise<Equipment[]> => mockRequest(equipment),
  getPrograms: (): Promise<Program[]> => mockRequest(programs),
  getTrainers: (): Promise<Trainer[]> => mockRequest(trainers),
  getTrainerById: (id: string): Promise<Trainer | undefined> =>
    mockRequest(trainers.find((t) => t.id === id)),
  getMembershipPlans: (): Promise<MembershipPlan[]> => mockRequest(membershipPlans),
  getTestimonials: (): Promise<Testimonial[]> => mockRequest(testimonials),
  getGalleryImages: (): Promise<GalleryImage[]> => mockRequest(galleryImages),
  submitContactForm: (payload: Record<string, string>): Promise<{ success: true }> =>
    mockRequest({ success: true as const }, 600).then((res) => {
      console.info("Mock contact submission", payload);
      return res;
    }),
};
