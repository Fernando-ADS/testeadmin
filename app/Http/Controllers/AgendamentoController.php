<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Coordenador;
use App\Models\Paciente;
use App\Models\Recepcionista;
use App\Models\Visitante;
use App\Models\Visita;
use App\Models\User;
use App\Http\Requests\StoreAgendamentoRequest;
use App\Http\Requests\UpdateAgendamentoRequest;
use App\Http\Requests\AjaxAgendamentoRequest;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

use Illuminate\Support\Facades\Mail;

class AgendamentoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */



    public function index()
    {
      //$this->authorize('is_admin');
      $agendamentos = Agendamento::orderBy('id')->get();
      return view('agendamentos.index', ['agendamentos' => $agendamentos]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $pacientes = Paciente::get();
      $visitantes = Visitante::get();
      $agendamentos = Agendamento::get();
      return view('agendamentos.create', ['pacientes'=>$pacientes, 'visitantes'=> $visitantes]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreAgendamentoRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAgendamentoRequest $request)
    {
      Agendamento::create($request->all());
      session()->flash('mensagem', 'Cadastrado com sucesso!');
      return redirect()->route('agendamentos.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Agendamento  $agendamento
     * @return \Illuminate\Http\Response
     */
    public function show(Agendamento $agendamento)
    {
      return view('agendamentos.show', ['agendamento' => $agendamento]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Agendamento  $agendamento
     * @return \Illuminate\Http\Response
     */
    public function edit(Agendamento $agendamento)
    {
      $pacientes = Paciente::get();
      $visitantes = Visitante::get();
      return view('agendamentos.edit', ['pacientes'=>$pacientes, 'visitantes'=> $visitantes, 'agendamento'=>$agendamento]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateAgendamentoRequest  $request
     * @param  \App\Models\Agendamento  $agendamento
     * @return \Illuminate\Http\Response
     */

     /*
     public function createVisita()
     {
       $pacientes = Paciente::get();
       $visitantes = Visitante::get();
       $visitas = Visita::get();
     }
     */

    public function update(UpdateAgendamentoRequest $request, Agendamento $agendamento)
    {
      $agendamento->fill($request->all());
      $agendamento->save();

      /*
      * Options value
      * 1 - Solicitado / 2 - Aprovado / 3 - Negado
      */

      $status_visita = $request->input('status_agendamento');

      //se o agendamento for aprovado, cria uma nova visita com os mesmos dados
      if ($status_visita == '2') {

        //Model Visita
        $paciente_id = $request->input('paciente_id');
        $visitante_id = $request->input('visitante_id');
        $data_visita = $request->input('data_agendamento');
        $hora_visita = $request->input('hora_agendamento');

        $nova_visita = new Visita();
        $nova_visita->fill(array('status_visita'=>$status_visita, 'paciente_id'=>$paciente_id, 'visitante_id'=>$visitante_id, 'data_visita'=>$data_visita,'hora_visita'=>$hora_visita));
        $nova_visita->save();


        //Pega o email do visitante correspondente
        $visitantes = Visitante::get();
        $email_visitante = null;
        foreach($visitantes as $e){
            if($e->id == $visitante_id){
              $email_visitante = $e->email;
            }
        }


        //Gera o QR Code com id da visita e salva na pasta
        $id_visita = $nova_visita->id;
        QrCode::size(200)->generate('qrrrrrrrrr','../resources/qrcodes/qrcode_visita_'.$id_visita.'.png');



        //Envia email para o visitante com o QR Code
        Mail::send('email.visitaConfirmada', ['data_visita'=>$data_visita, 'hora_visita'=>$hora_visita], function($mensagem) use ($email_visitante, $data_visita, $hora_visita, $id_visita){
            $mensagem->from('visitsys.gestao@gmail.com','VisitSys | Gest??o Hospitalar');
            $mensagem->to($email_visitante);
            $mensagem->subject('Resultado do Agendamento');
            $mensagem->attach('../resources/qrcodes/qrcode_visita_'.$id_visita.'.png');
        });
      }


      //se o agendamento n??o for aprovado, envia email avisando que foi negado
      if ($status_visita == '3') {

        //Pega o email do visitante correspondente
        $visitante_id = $request->input('visitante_id');
        $visitantes = Visitante::get();
        $email_visitante = null;
        foreach($visitantes as $e){
            if($e->id == $visitante_id){
              $email_visitante = $e->email;
            }
        }

        Mail::send('email.visitaNegada', [], function($mensagem) use ($email_visitante){
            $mensagem->from('visitsys.gestao@gmail.com','VisitSys | Gest??o Hospitalar');
            $mensagem->to($email_visitante);
            $mensagem->subject('Resultado do Agendamento');
        });
      }


      session()->flash('mensagem', 'Atualizado com sucesso!');
      return redirect()->route('agendamentos.index');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Agendamento  $agendamento
     * @return \Illuminate\Http\Response
     */
    public function destroy(Agendamento $agendamento)
    {
      {
        $agendamento->delete();
        session()->flash('mensagem', 'Exclu??do com sucesso!');
        return redirect()->route('agendamentos.index');
      }
    }

    public function search()
    {
      $this->authorize('is_admin');
      $pesquisa = $_GET['search'];
      $agendamentos = Agendamento::where('paciente_id','LIKE','%'.$pesquisa.'%')->get();

      return view('agendamentos.search', compact('agendamentos'));
    }


    public function procuraPaciente(AjaxAgendamentoRequest $request){
      $pacientes = Paciente::all();
      $inputPac = ($request->nome_paciente);

      foreach ($pacientes as $p) {
        if($inputPac == $p->nome){
          //var_dump('sucesso');
          $idPac['success'] = true;
          $idPac['id'] = $p->id;
          $retorno = json_encode($idPac);
          return($retorno);
        }
      }

      $idPac['success'] = false;
      $idPac['id'] = null;
      $retorno = json_encode($idPac);
      return($retorno);

    }

}
