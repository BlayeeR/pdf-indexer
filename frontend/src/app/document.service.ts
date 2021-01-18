import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { PdfFile } from '@models/PdfFile';
import { Date as PdfDate } from '@models/Date';
import { Amount, Vat } from '@models/Amount';
import { Info } from '@models/Info';

@Injectable({
  providedIn: 'root'
})
export class DocumentService {

  public documentConditions: DocumentConditions = new DocumentConditions();

  constructor(private httpClient: HttpClient) { }

  public findDates(fileId: number, searchRadius: number): Observable<PdfFile> {
    const endpoint = 'http://localhost/api/files/finddates?id=' + fileId + '&searchradius=' + searchRadius;
      return this.httpClient
      .get<PdfFile>(endpoint);
  }

  public findAmounts(fileId: number, searchRadius: number): Observable<PdfFile> {
    const endpoint = 'http://localhost/api/files/findamounts?id=' + fileId + '&searchradius=' + searchRadius;
      return this.httpClient
      .get<PdfFile>(endpoint);
  }

  public findInfo(fileId: number, searchRadius: number): Observable<PdfFile> {
    const endpoint = 'http://localhost/api/files/findinfo?id=' + fileId + '&searchradius=' + searchRadius;
      return this.httpClient
      .get<PdfFile>(endpoint);
  }

  public saveDocument(fileId: number, conditions: DocumentConditions): Observable<PdfFile> {
    const endpoint = 'http://localhost/api/files/savedocument?id=' + fileId;
      return this.httpClient
      .post<PdfFile>(endpoint, conditions);
  }
}

export class DocumentConditions {
  Id: number;
  Name: string;
  DateSearchRadius: number = 15;
  AmountSearchRadius: number = 10;
  InfoSearchRadius: number = 25;
  Dates: PdfDate[] = [];
  Amounts: Amount[] = [];
  Infos: Info[] = [];
  Title: string;
}

export enum Type {
  Date = 1,
  Amount = 2,
  Info =3
}
